<?php

namespace App\Http\Controllers;

use App\Exports\CierreCajaVentasExport;
use App\Models\CierreCaja;
use App\Models\User;
use App\Models\Venta;
use App\Services\ArqueoCaja;
use App\Services\ReporteCierreCaja;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Cierre de caja diario por usuario.
 *
 * Reglas del negocio:
 *  - Un solo cierre por usuario y por día (índice único en la tabla).
 *  - Volver a cerrar el mismo día no recalcula nada: devuelve el cierre guardado
 *    con el mismo monto.
 *  - La diferencia es solo de efectivo: efectivo declarado − efectivo esperado
 *    (efectivo de las ventas − gastos en efectivo). El QR y los otros medios se
 *    informan aparte (ver ArqueoCaja). Los cierres anteriores a este cambio
 *    (formula = null) no se modifican: se muestran calculados al vuelo.
 *  - El cierre admite una sola corrección, hecha por el mismo usuario que cerró.
 *  - Con la caja cerrada, ese usuario ya no puede registrar ventas ese día.
 *  - Quien tiene 'Validar Cierres Caja' valida el cierre (queda quién y cuándo);
 *    validado, el cierre queda fijo y ya no admite correcciones ni anulaciones.
 *  - Quien tiene 'Autorizar Ventas Caja Cerrada' da tiempo extra a un usuario
 *    que ya cerró: cada operación de ese tiempo recalcula el cierre y el usuario
 *    vuelve a tener una corrección para su efectivo.
 *  - Jornada anterior sin cierre (turno de noche): el propio cajero la cierra
 *    hasta las 12:00 del día siguiente; después, hasta 2 días atrás, solo quien
 *    tiene 'Autorizar Ventas Caja Cerrada', en nombre del cajero.
 *  - El cierre se recalcula solo al cerrar, corregir, validar y tras una
 *    operación autorizada; nunca al consultarlo.
 */
class CierreCajaController extends Controller
{
    private const RELACIONES = ['user:id,name,username', 'validadoPor:id,name,username', 'autorizadoPor:id,name,username'];

    /** Fórmula de los cierres nuevos (diferencia solo de efectivo). */
    public const FORMULA = 'EFECTIVO';

    /** Días hacia atrás en que se puede cerrar una jornada que quedó sin cierre. */
    private const DIAS_JORNADA_ANTERIOR = 2;

    /** Hasta qué hora del día siguiente el cajero cierra él mismo su jornada anterior. */
    private const HORA_LIMITE_CAJERO = 12;

    public function index(Request $request)
    {
        $this->req($request, 'Ver Cierres Caja');

        $query = CierreCaja::with(self::RELACIONES)
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $verMontos = $request->user()->can('Ver Montos Caja');
        $totales = $verMontos ? self::totales((clone $query)->get()) : null;

        $cierres = $query->paginate((int) $request->input('per_page', 15));

        // Sin 'Ver Montos Caja' solo se ve lo declarado, nunca el esperado ni la diferencia.
        $cierres->getCollection()->transform(fn ($cierre) => $this->ocultarMontos(self::conArqueo($cierre), $request->user()));

        return response()->json([...$cierres->toArray(), 'totales' => $totales]);
    }

    /**
     * Estado de la caja de hoy del usuario conectado.
     *
     * El cierre es a ciegas: el arqueo del día TODAVÍA ABIERTO solo viaja a quien
     * tiene 'Ver Montos Caja', para que el cajero declare el efectivo que entrega
     * sin ver cuánto debería ser. Una vez cerrado, el cierre se devuelve completo.
     */
    public function estado(Request $request)
    {
        $this->req($request, 'Cerrar Caja');

        $user = $request->user();
        $fecha = $this->hoy();
        $cierre = self::cierreDelDia($user->id, $fecha);
        $verMontos = $user->can('Ver Montos Caja');
        $arqueo = $verMontos ? ArqueoCaja::calcular(self::ventasDelDia($user->id, $fecha)) : null;

        return response()->json([
            'fecha' => $fecha,
            // 'cerrada' es si hoy no puede vender: con autorización vigente sigue abierta.
            'cerrada' => (bool) $cierre && ! $cierre->autorizado,
            'ver_montos' => $verMontos,
            'cierre' => $this->ocultarMontos($cierre ? self::conArqueo($cierre->load(self::RELACIONES)) : null, $user),
            'arqueo' => $arqueo ? collect($arqueo)->except('monto_sistema')->all() : null,
            'total_sistema' => $arqueo['monto_sistema'] ?? null,
            'cantidad_ventas' => $arqueo['cantidad_ventas'] ?? null,
            'jornada_pendiente' => self::jornadaPendiente($user->id),
        ]);
    }

    /**
     * Cierra la caja. Sin 'fecha', la de hoy del usuario conectado. Con 'fecha',
     * una jornada anterior que quedó sin cierre: la del propio cajero (hasta las
     * 12:00 del día siguiente) o, con 'user_id', la de otro cajero en su nombre
     * (solo con 'Autorizar Ventas Caja Cerrada', con comentario).
     */
    public function store(Request $request)
    {
        $this->req($request, ['Cerrar Caja', 'Autorizar Ventas Caja Cerrada']);

        $this->validarCierre($request);
        $request->validate([
            'fecha' => 'nullable|date_format:Y-m-d',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $actor = $request->user();
        $hoy = $this->hoy();
        $fecha = $request->input('fecha') ?: $hoy;
        $enNombre = $request->filled('user_id');
        $userId = $enNombre ? (int) $request->user_id : $actor->id;

        if ($enNombre) {
            if (! $actor->hasPermissionTo('Autorizar Ventas Caja Cerrada')) {
                abort(403, 'No tiene permiso para cerrar la caja de otro usuario');
            }
            if ($fecha === $hoy) {
                abort(422, 'En nombre de otro usuario solo se cierra una jornada anterior que quedó sin cierre');
            }
            if (! filled($request->comentario)) {
                abort(422, 'Indique en el comentario por qué cierra la caja en nombre del cajero');
            }
        } elseif (! $actor->hasPermissionTo('Cerrar Caja')) {
            abort(403, 'No tiene permiso para realizar esta acción');
        }

        // Si ya cerró esa fecha, se devuelve el mismo cierre con el mismo monto.
        if ($cierre = self::cierreDelDia($userId, $fecha)) {
            return response()->json([
                'message' => 'La caja de '.($fecha === $hoy ? 'hoy' : 'esa fecha').' ya fue cerrada',
                'cierre' => $this->ocultarMontos(self::conArqueo($cierre->load(self::RELACIONES)), $actor),
                'ya_existia' => true,
            ]);
        }

        if ($fecha !== $hoy) {
            $this->validarJornadaAnterior($userId, $fecha, $enNombre);
        }

        [$monto, $detalle] = $this->efectivoDeclarado($request);
        $cierre = new CierreCaja([
            'user_id' => $userId,
            'fecha' => $fecha,
            'monto' => $monto,
            'detalle_efectivo' => $detalle,
            'fecha_hora' => now(),
            'comentario' => $request->comentario ?: null,
            'formula' => self::FORMULA,
        ]);
        self::aplicarCalculo($cierre);
        $cierre->save();

        return response()->json([
            'message' => 'Caja cerrada',
            'cierre' => $this->ocultarMontos($cierre->load(self::RELACIONES), $actor),
            'ya_existia' => false,
        ], 201);
    }

    /** Única corrección permitida, y solo por el usuario que cerró. */
    public function update(Request $request, $id)
    {
        $this->req($request, 'Cerrar Caja');

        $this->validarCierre($request);

        $cierre = CierreCaja::findOrFail($id);
        $user = $request->user();

        if ((int) $cierre->user_id !== (int) $user->id) {
            abort(403, 'Solo el usuario que cerró la caja puede modificar el cierre');
        }
        if ($cierre->validado) {
            abort(422, 'Este cierre ya fue validado y no admite cambios');
        }
        if (! $cierre->puede_modificar) {
            abort(422, 'Este cierre ya fue modificado una vez y no admite más cambios');
        }

        [$monto, $detalle] = $this->efectivoDeclarado($request);
        $cierre->fill([
            'monto' => $monto,
            'detalle_efectivo' => $detalle,
            'comentario' => $request->comentario ?: null,
            'modificado_en' => now(),
        ]);
        // Corregir es una escritura: recalcula el arqueo con el efectivo nuevo.
        self::aplicarCalculo($cierre, forzar: true);
        $cierre->save();

        return response()->json([
            'message' => 'Cierre modificado',
            'cierre' => $this->ocultarMontos(self::conArqueo($cierre->load(self::RELACIONES)), $user),
        ]);
    }

    /** Valida el cierre: recalcula por última vez, registra quién y cuándo, y lo deja fijo. */
    public function validar(Request $request, $id)
    {
        $this->req($request, 'Validar Cierres Caja');

        $cierre = CierreCaja::findOrFail($id);
        if ($cierre->validado) {
            abort(422, 'Este cierre ya fue validado');
        }
        if ($cierre->autorizado) {
            abort(422, 'El usuario todavía tiene tiempo autorizado para vender: valide cuando termine');
        }
        self::recalcular($cierre);

        $cierre->update([
            'validado_por_id' => $request->user()->id,
            'validado_en' => now(),
        ]);

        return response()->json([
            'message' => 'Cierre validado',
            'cierre' => $this->ocultarMontos(self::conArqueo($cierre->load(self::RELACIONES)), $request->user()),
        ]);
    }

    /**
     * Da tiempo extra para vender con la caja ya cerrada. Solo el cierre de hoy
     * y sin validar. Se le devuelve la corrección al usuario para que, al
     * terminar, declare el efectivo final con las nuevas ventas.
     */
    public function autorizar(Request $request, $id)
    {
        $this->req($request, 'Autorizar Ventas Caja Cerrada');

        $request->validate(['minutos' => 'required|integer|min:5|max:720']);

        $cierre = CierreCaja::findOrFail($id);
        if ($cierre->validado) {
            abort(422, 'Este cierre ya fue validado: no se puede autorizar más ventas');
        }
        if ($cierre->fecha->toDateString() !== $this->hoy()) {
            abort(422, 'Solo se puede autorizar más ventas en el cierre de hoy');
        }

        // La autorización no pasa de la medianoche: al día siguiente la caja es otra.
        $hasta = now()->addMinutes((int) $request->minutos)->min(now()->endOfDay());

        $cierre->update([
            'autorizado_hasta' => $hasta,
            'autorizado_por_id' => $request->user()->id,
            'modificado_en' => null,
        ]);

        return response()->json([
            'message' => 'Ventas autorizadas hasta las '.$hasta->format('H:i'),
            'cierre' => $this->ocultarMontos(self::conArqueo($cierre->load(self::RELACIONES)), $request->user()),
        ]);
    }

    /**
     * Jornadas de los últimos días con movimientos y sin cierre, de todos los
     * usuarios: las que el supervisor puede cerrar en nombre del cajero.
     */
    public function jornadasPendientes(Request $request)
    {
        $this->req($request, 'Autorizar Ventas Caja Cerrada');

        $jornadas = [];
        foreach (self::diasJornadaAnterior() as $fecha) {
            $usuarios = Venta::query()
                ->where(fn ($q) => $q->where('estado', 'ACTIVO')->whereDate('fecha_hora', $fecha))
                ->pluck('user_id')
                ->merge(Venta::where('estado', 'PENDIENTE')->whereNotNull('cobrado_por_id')
                    ->whereDate('fecha_hora_cobro', $fecha)->pluck('cobrado_por_id'))
                ->filter()->unique();
            $cerrados = CierreCaja::whereDate('fecha', $fecha)->pluck('user_id')->all();
            foreach ($usuarios->diff($cerrados) as $userId) {
                $jornadas[] = [
                    'user' => User::select('id', 'name', 'username')->find($userId),
                    'fecha' => $fecha,
                    'movimientos' => self::ventasDelDia($userId, $fecha)->count(),
                ];
            }
        }

        return response()->json(collect($jornadas)->sortBy('fecha')->values());
    }

    /**
     * Ventas que componen un cierre ya guardado: por defecto las del usuario en
     * la fecha del cierre. Con 'desde' y 'hasta' (fecha y hora) se ven las de
     * ese rango, para turnos que cruzan la medianoche o duran media hora.
     */
    public function ventas(Request $request, $id)
    {
        $this->req($request, 'Ver Cierres Caja');

        $cierre = self::conArqueo(CierreCaja::with(self::RELACIONES)->findOrFail($id));
        $rango = $this->rangoSolicitado($request);
        $verMontos = $request->user()->can('Ver Montos Caja');

        $ventas = $this->ventasDelCierre($cierre, $rango)
            ->with([
                'paciente:id,nombre_completo,ci',
                'user:id,name',
                'cobradoPor:id,name',
                'detalles:id,venta_id,nombre,cantidad,precio,precio_original,total',
            ])
            ->orderBy('fecha_hora')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        // Sin 'Ver Montos Caja' el detalle viaja sin importes.
        if (! $verMontos) {
            $ventas->getCollection()->each(function (Venta $venta) {
                $venta->makeHidden(['total', 'total_original', 'pago', 'cambio', 'monto_efectivo', 'monto_qr']);
                $venta->detalles->each->makeHidden(['precio', 'precio_original', 'total']);
            });
        }

        $resumen = self::resumen($this->ventasDelCierre($cierre, $rango));
        if (! $verMontos) {
            $resumen = [...$resumen, 'ingresos' => null, 'egresos' => null, 'neto' => null];
        }

        return response()->json([
            'cierre' => $this->ocultarMontos($cierre, $request->user()),
            'ventas' => $ventas,
            'rango' => $rango ? self::rangoTexto($rango) : null,
            'resumen' => $resumen,
            'resumen_caja' => self::resumenCaja($cierre, $rango, $verMontos),
            'ver_montos' => $verMontos,
        ]);
    }

    public function ventasExportExcel(Request $request, $id)
    {
        $this->req($request, 'Ver Cierres Caja');

        $cierre = CierreCaja::with(self::RELACIONES)->findOrFail($id);
        $rango = $this->rangoSolicitado($request);
        $verMontos = $request->user()->can('Ver Montos Caja');

        return Excel::download(
            new CierreCajaVentasExport(
                $this->ventasDelCierre($cierre, $rango),
                self::resumenCaja($cierre, $rango, $verMontos),
                $verMontos
            ),
            'cierre_caja_'.$cierre->fecha->format('Ymd').'_'.$cierre->id.($rango ? '_'.$rango[0]->format('Ymd_Hi').'-'.$rango[1]->format('Ymd_Hi') : '').'.xlsx'
        );
    }

    public function ventasExportPdf(Request $request, $id)
    {
        $this->req($request, 'Ver Cierres Caja');
        ini_set('memory_limit', '1024M');

        $cierre = self::conArqueo(CierreCaja::with(self::RELACIONES)->findOrFail($id));
        $rango = $this->rangoSolicitado($request);
        $verMontos = $request->user()->can('Ver Montos Caja');

        $ventas = $this->ventasDelCierre($cierre, $rango)
            ->with(['paciente:id,nombre_completo,ci', 'detalles:id,venta_id,nombre,cantidad,precio,precio_original,total'])
            ->orderBy('fecha_hora')
            ->get();

        $pdf = Pdf::loadView('reportes.cierre-caja-ventas', [
            'cierre' => $cierre,
            'ventas' => $ventas,
            'rango' => $rango,
            'resumen' => self::resumen($this->ventasDelCierre($cierre, $rango)),
            'resumenCaja' => self::resumenCaja($cierre, $rango, $verMontos),
            'verMontos' => $verMontos,
        ])->setPaper('letter');

        return $pdf->stream('cierre_caja_'.$cierre->fecha->format('Ymd').'_'.$cierre->id.'.pdf');
    }

    /** Reporte completo de un cierre validado (datos para la pantalla). */
    public function reporte(Request $request, $id)
    {
        $cierre = $this->cierreParaReporte($request, $id);

        return response()->json(ReporteCierreCaja::datos($cierre));
    }

    /** Reporte completo de un cierre validado en PDF. */
    public function reportePdf(Request $request, $id)
    {
        $cierre = $this->cierreParaReporte($request, $id);
        ini_set('memory_limit', '1024M');

        return Pdf::loadView('reportes.cierre-caja-reporte', ['r' => ReporteCierreCaja::datos($cierre)])
            ->setPaper('letter')
            ->stream('reporte_cierre_caja_'.$cierre->fecha->format('Ymd').'_'.$cierre->id.'.pdf');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /** Cortes de billetes y monedas en Bolivia (Bs). */
    public const CORTES = ['200', '100', '50', '20', '10', '5', '2', '1', '0.5', '0.2', '0.1'];

    /** El reporte exige ver los cierres y sus montos (403) y un cierre validado (422). */
    private function cierreParaReporte(Request $request, $id): CierreCaja
    {
        foreach (['Ver Cierres Caja', 'Ver Montos Caja'] as $permiso) {
            if (! $request->user()->hasPermissionTo($permiso)) {
                abort(403, "No tiene el permiso '{$permiso}' para ver el reporte de cierre de caja");
            }
        }
        $cierre = CierreCaja::with(self::RELACIONES)->findOrFail($id);
        if (! $cierre->validado) {
            abort(422, 'El reporte de cierre de caja solo está disponible para cierres validados');
        }

        return $cierre;
    }

    private function validarCierre(Request $request): void
    {
        $request->validate([
            'monto' => 'required|numeric|min:0',
            // Las claves "0.5", "0.2"... chocan con la notación de puntos de
            // Laravel: las cantidades se validan en efectivoDeclarado().
            'detalle_efectivo' => 'nullable|array',
            'comentario' => 'nullable|string|max:500',
        ]);
    }

    /**
     * El monto que cuenta es el que digita el cajero. El conteo por cortes es
     * solo una ayuda para sumar: se guarda como referencia si trae algo.
     *
     * @return array{0: float, 1: array<string,int>|null}
     */
    private function efectivoDeclarado(Request $request): array
    {
        $monto = round((float) $request->monto, 2);
        $conteo = $request->input('detalle_efectivo');
        if (! is_array($conteo)) {
            return [$monto, null];
        }

        $detalle = [];
        foreach (self::CORTES as $corte) {
            $valor = $conteo[$corte] ?? 0;
            if ($valor === null || $valor === '') {
                $valor = 0;
            }
            if (filter_var($valor, FILTER_VALIDATE_INT) === false || (int) $valor < 0) {
                abort(422, "Cantidad inválida para el corte de {$corte} Bs");
            }
            $detalle[$corte] = (int) $valor;
        }

        return [$monto, array_sum($detalle) > 0 ? $detalle : null];
    }

    /**
     * Sin 'Ver Montos Caja' el cierre viaja sin el esperado ni la diferencia:
     * la cajera solo ve el efectivo que ella misma declaró.
     */
    private function ocultarMontos(?CierreCaja $cierre, $user): ?CierreCaja
    {
        if ($cierre && ! $user->can('Ver Montos Caja')) {
            $cierre->makeHidden(CierreCaja::CAMPOS_MONTO);
        }

        return $cierre;
    }

    /**
     * Cierre anterior a la fórmula de efectivo (formula = null): se le agregan
     * al vuelo, SIN guardar, las cifras del arqueo calculadas con los
     * movimientos actuales. Los cierres nuevos ya las traen guardadas.
     */
    public static function conArqueo(CierreCaja $cierre): CierreCaja
    {
        if ($cierre->formula !== null) {
            return $cierre;
        }
        $calculo = ArqueoCaja::calcular(self::ventasDelDia($cierre->user_id, $cierre->fecha->toDateString()));
        foreach (['ingresos_total', 'efectivo_ingresos', 'qr_total', 'qr_cantidad', 'otros_total', 'otros_cantidad',
            'gastos_efectivo', 'gastos_cantidad', 'efectivo_esperado'] as $campo) {
            $cierre->setAttribute($campo, $calculo[$campo]);
        }
        $cierre->setAttribute('diferencia_efectivo', round((float) $cierre->monto - $calculo['efectivo_esperado'], 2));

        return $cierre;
    }

    /** Totales de todos los cierres del filtro (no solo de la página). */
    private static function totales($cierres): array
    {
        $cierres = $cierres->map(fn ($cierre) => self::conArqueo($cierre));

        return [
            'cantidad' => $cierres->count(),
            'declarado' => round((float) $cierres->sum('monto'), 2),
            'efectivo_esperado' => round((float) $cierres->sum('efectivo_esperado'), 2),
            'diferencia_efectivo' => round((float) $cierres->sum('diferencia_efectivo'), 2),
            'qr_total' => round((float) $cierres->sum('qr_total'), 2),
            'otros_total' => round((float) $cierres->sum('otros_total'), 2),
        ];
    }

    /** Máximo de días que abarca el filtro por fecha y hora de las ventas. */
    private const RANGO_MAX_DIAS = 31;

    /**
     * Rango 'desde'/'hasta' (fecha y hora de Bolivia) pedido para ver las
     * ventas de un cierre, o null si no se pidió. 'hasta' incluye todo ese
     * minuto: hasta las 08:00 abarca las ventas de las 08:00:59.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function rangoSolicitado(Request $request): ?array
    {
        if (! $request->filled('desde') && ! $request->filled('hasta')) {
            return null;
        }

        $request->validate([
            'desde' => 'required|date',
            'hasta' => 'required|date|after_or_equal:desde',
        ], [
            'desde.required' => 'Indique desde qué fecha y hora',
            'hasta.required' => 'Indique hasta qué fecha y hora',
            'hasta.after_or_equal' => 'La fecha y hora "hasta" debe ser posterior a "desde"',
        ]);

        $desde = Carbon::parse($request->desde)->startOfMinute();
        $hasta = Carbon::parse($request->hasta)->startOfMinute();

        if ($desde->diffInDays($hasta) > self::RANGO_MAX_DIAS) {
            abort(422, 'El rango no puede pasar de '.self::RANGO_MAX_DIAS.' días');
        }

        return [$desde, $hasta];
    }

    /** Ventas del cierre: las de su día, o las del usuario en el rango pedido. */
    private static function ventasDelCierre(CierreCaja $cierre, ?array $rango)
    {
        return $rango
            ? self::ventasEnRango($cierre->user_id, $rango[0], $rango[1])
            : self::ventasDelDia($cierre->user_id, $cierre->fecha->toDateString());
    }

    /**
     * Ventas que entran a la caja de un usuario entre dos momentos: las que
     * registró cobradas y las pendientes que él cobró en ese lapso.
     */
    public static function ventasEnRango(int $userId, Carbon $desde, Carbon $hasta)
    {
        $inicio = $desde->format('Y-m-d H:i:00');
        $fin = $hasta->copy()->addMinute()->format('Y-m-d H:i:00');

        return Venta::where(function ($query) use ($userId, $inicio, $fin) {
            $query->where(function ($directas) use ($userId, $inicio, $fin) {
                $directas->where('user_id', $userId)
                    ->where('estado', 'ACTIVO')
                    ->where('fecha_hora', '>=', $inicio)
                    ->where('fecha_hora', '<', $fin);
            })->orWhere(function ($cobros) use ($userId, $inicio, $fin) {
                $cobros->where('estado', 'PENDIENTE')
                    ->where('cobrado_por_id', $userId)
                    ->where('fecha_hora_cobro', '>=', $inicio)
                    ->where('fecha_hora_cobro', '<', $fin);
            });
        });
    }

    /** @param array{0: Carbon, 1: Carbon} $rango */
    private static function rangoTexto(array $rango): array
    {
        return ['desde' => $rango[0]->format('Y-m-d H:i'), 'hasta' => $rango[1]->format('Y-m-d H:i')];
    }

    /** Totales de todos los movimientos de la consulta (no solo de la página). */
    private static function resumen($ventas): array
    {
        $ingresos = (float) (clone $ventas)->where('tipo_movimiento', 'INGRESO')->sum('total');
        $egresos = (float) (clone $ventas)->where('tipo_movimiento', 'EGRESO')->sum('total');

        return [
            'ingresos' => round($ingresos, 2),
            'egresos' => round($egresos, 2),
            'neto' => round($ingresos - $egresos, 2),
            'cantidad_ventas' => (clone $ventas)->where('tipo_movimiento', 'INGRESO')->count(),
            'cantidad_gastos' => (clone $ventas)->where('tipo_movimiento', 'EGRESO')->count(),
        ];
    }

    /**
     * Resumen de caja al pie del detalle del cierre, con la misma definición del
     * arqueo: los movimientos que entraron a la caja del usuario (sus ventas
     * cobradas, las pendientes que él cobró y sus gastos) en el día del cierre
     * o en el rango pedido. Las pendientes que el usuario registró y siguen sin
     * cobrar se suman al total y se restan como pendientes. Se calcula con los
     * estados actuales; sin 'Ver Montos Caja' viaja sin importes.
     *
     * TOTAL EFECTIVO = total ventas − pendientes − QR − otros medios − gastos
     *                = efectivo esperado del arqueo.
     */
    public static function resumenCaja(CierreCaja $cierre, ?array $rango, bool $verMontos): array
    {
        $registradas = function () use ($cierre, $rango) {
            $query = Venta::where('user_id', $cierre->user_id)->where('tipo_movimiento', 'INGRESO');

            return $rango
                ? $query->where('fecha_hora', '>=', $rango[0]->format('Y-m-d H:i:00'))
                    ->where('fecha_hora', '<', $rango[1]->copy()->addMinute()->format('Y-m-d H:i:00'))
                : $query->whereDate('fecha_hora', $cierre->fecha->toDateString());
        };
        $linea = fn ($query) => ['cantidad' => (clone $query)->count(), 'importe' => round((float) (clone $query)->sum('total'), 2)];

        $arqueo = ArqueoCaja::calcular(self::ventasDelCierre($cierre, $rango));
        $anuladas = $linea($registradas()->where('estado', 'ANULADO'));
        $pendientes = $linea($registradas()->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro'));
        $total = [
            'cantidad' => $arqueo['cantidad_ventas'] + $pendientes['cantidad'],
            'importe' => round($arqueo['ingresos_total'] + $pendientes['importe'], 2),
        ];

        $resumen = [
            'anuladas' => $anuladas,
            'total_ventas' => $total,
            'pendientes' => $pendientes,
            'qr' => ['cantidad' => $arqueo['qr_cantidad'], 'importe' => $arqueo['qr_total']],
            'otros' => ['cantidad' => $arqueo['otros_cantidad'], 'importe' => $arqueo['otros_total']],
            'gastos' => ['cantidad' => $arqueo['gastos_cantidad'], 'importe' => $arqueo['gastos_efectivo']],
            'total_efectivo' => round($total['importe'] - $pendientes['importe'] - $arqueo['qr_total']
                - $arqueo['otros_total'] - $arqueo['gastos_efectivo'], 2),
        ];

        if (! $verMontos) {
            foreach ($resumen as $clave => $valor) {
                $resumen[$clave] = is_array($valor) ? [...$valor, 'importe' => null] : null;
            }
        }

        return $resumen;
    }

    /** Cierre vigente de un usuario en una fecha, o null. */
    public static function cierreDelDia(int $userId, string $fecha): ?CierreCaja
    {
        return CierreCaja::where('user_id', $userId)->whereDate('fecha', $fecha)->first();
    }

    /**
     * Cierre que contiene un movimiento: el de quien lo registró en su día (venta
     * cobrada o gasto) o el de quien cobró la pendiente, el día del cobro. Una
     * pendiente sin cobrar no pertenece a ninguna caja.
     */
    public static function cierreQueContiene(Venta $venta): ?CierreCaja
    {
        if ($venta->estado === 'PENDIENTE') {
            return $venta->cobrado_por_id && $venta->fecha_hora_cobro
                ? self::cierreDelDia($venta->cobrado_por_id, $venta->fecha_hora_cobro->toDateString())
                : null;
        }

        return $venta->user_id ? self::cierreDelDia($venta->user_id, $venta->fecha_hora->toDateString()) : null;
    }

    /**
     * El usuario no puede vender ni cobrar si ya cerró hoy, salvo que tenga una
     * autorización vigente.
     */
    public static function cajaBloqueada(int $userId, string $fecha): bool
    {
        $cierre = self::cierreDelDia($userId, $fecha);

        return $cierre !== null && ! $cierre->autorizado;
    }

    /**
     * Recalcula y guarda un cierre sin validar. Se llama solo desde operaciones
     * que escriben: validar, una venta, gasto o cobro hecho con tiempo
     * autorizado, y una anulación permitida sobre la caja cerrada.
     */
    public static function recalcular(?CierreCaja $cierre): ?CierreCaja
    {
        if (! $cierre || $cierre->validado) {
            return $cierre;
        }
        self::aplicarCalculo($cierre);
        if ($cierre->isDirty()) {
            $cierre->save();
        }

        return $cierre;
    }

    /** Tras una operación de caja: recalcula el cierre de ese usuario y fecha, si existe. */
    public static function recalcularCaja(int $userId, string $fecha): void
    {
        self::recalcular(self::cierreDelDia($userId, $fecha));
    }

    /**
     * Llena las cifras del cierre. Fórmula de efectivo: todo el arqueo, más
     * monto_sistema y diferencia como se guardaban antes. Cierre anterior
     * (formula = null): solo lo de siempre (monto_sistema, cantidad y diferencia
     * contra el total), y solo si tuvo una autorización o se fuerza al corregir;
     * sus columnas nuevas siguen en null.
     */
    private static function aplicarCalculo(CierreCaja $cierre, bool $forzar = false): void
    {
        $nueva = $cierre->formula === self::FORMULA;
        if (! $nueva && $cierre->autorizado_hasta === null) {
            // Cierre anterior sin autorización: como siempre, el total guardado no
            // se recalcula; al corregir solo cambia la diferencia contra ese total.
            if ($forzar) {
                $cierre->diferencia = round((float) $cierre->monto - (float) $cierre->monto_sistema, 2);
            }

            return;
        }

        $calculo = ArqueoCaja::calcular(self::ventasDelDia($cierre->user_id, Carbon::parse($cierre->fecha)->toDateString()));
        $monto = (float) $cierre->monto;
        $cierre->fill([
            'monto_sistema' => $calculo['monto_sistema'],
            'cantidad_ventas' => $calculo['cantidad_ventas'],
            'diferencia' => round($monto - $calculo['monto_sistema'], 2),
        ]);
        if ($nueva) {
            $cierre->fill([
                ...collect($calculo)->only(['ingresos_total', 'efectivo_ingresos', 'qr_total', 'qr_cantidad', 'otros_total',
                    'otros_cantidad', 'gastos_efectivo', 'gastos_cantidad', 'efectivo_esperado'])->all(),
                'diferencia_efectivo' => round($monto - $calculo['efectivo_esperado'], 2),
            ]);
        }
    }

    /** Fechas en que se puede cerrar una jornada anterior, de la más antigua a ayer. */
    private static function diasJornadaAnterior(): array
    {
        return collect(range(self::DIAS_JORNADA_ANTERIOR, 1))
            ->map(fn ($dias) => now()->subDays($dias)->toDateString())
            ->all();
    }

    /**
     * Jornada anterior del usuario con movimientos y sin cierre (la más antigua
     * de los últimos 2 días), o null. 'puede_cerrar': el propio cajero la cierra
     * solo si es la de ayer y antes de las 12:00 de hoy.
     */
    public static function jornadaPendiente(int $userId): ?array
    {
        foreach (self::diasJornadaAnterior() as $fecha) {
            if (self::cierreDelDia($userId, $fecha)) {
                continue;
            }
            $movimientos = self::ventasDelDia($userId, $fecha)->count();
            if ($movimientos > 0) {
                $limite = Carbon::parse($fecha)->addDay()->setTime(self::HORA_LIMITE_CAJERO, 0);

                return [
                    'fecha' => $fecha,
                    'movimientos' => $movimientos,
                    'puede_cerrar' => now()->lt($limite),
                    'limite' => $limite->format('Y-m-d H:i'),
                ];
            }
        }

        return null;
    }

    /** Una jornada anterior solo se cierra dentro de los plazos (ver jornadaPendiente). */
    private function validarJornadaAnterior(int $userId, string $fecha, bool $enNombre): void
    {
        if (! in_array($fecha, self::diasJornadaAnterior(), true)) {
            abort(422, 'Solo se puede cerrar una jornada de los últimos '.self::DIAS_JORNADA_ANTERIOR.' días');
        }
        if (! self::ventasDelDia($userId, $fecha)->exists()) {
            abort(422, 'Esa jornada no tiene movimientos para cerrar');
        }
        if ($enNombre) {
            return;
        }
        $pendiente = self::jornadaPendiente($userId);
        if (! $pendiente || $pendiente['fecha'] !== $fecha) {
            abort(422, 'Primero debe cerrarse la jornada pendiente más antigua');
        }
        if (! $pendiente['puede_cerrar']) {
            abort(422, 'El plazo para cerrar la caja del '.Carbon::parse($fecha)->format('d/m/Y')
                .' venció a las '.self::HORA_LIMITE_CAJERO.':00 del día siguiente: pida a un supervisor que la cierre');
        }
    }

    private function hoy(): string
    {
        return now()->toDateString();
    }

    /**
     * Ventas que entran a la caja de un usuario en un día: las que registró
     * cobradas y las pendientes que él cobró ese día.
     */
    public static function ventasDelDia(int $userId, string $fecha)
    {
        return Venta::where(function ($query) use ($userId, $fecha) {
            $query->where(function ($directas) use ($userId, $fecha) {
                $directas->where('user_id', $userId)
                    ->where('estado', 'ACTIVO')
                    ->whereDate('fecha_hora', $fecha);
            })->orWhere(function ($cobros) use ($userId, $fecha) {
                $cobros->where('estado', 'PENDIENTE')
                    ->where('cobrado_por_id', $userId)
                    ->whereDate('fecha_hora_cobro', $fecha);
            });
        });
    }

    private function req(Request $request, string|array $permission): void
    {
        $user = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
