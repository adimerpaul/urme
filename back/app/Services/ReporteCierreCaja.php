<?php

namespace App\Services;

use App\Http\Controllers\CierreCajaController;
use App\Models\CierreCaja;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

/**
 * Reporte completo de un cierre de caja validado (docs/ANALISIS_CIERRES_CAJA.md §8.9).
 * La pantalla y el PDF usan este mismo arreglo. El arqueo sale de las cifras
 * guardadas del cierre; en un cierre anterior a la fórmula de efectivo se
 * calcula al vuelo y se muestra también la diferencia registrada. Las demás
 * secciones salen de los movimientos de la caja (CierreCajaController::ventasDelDia)
 * y el reporte verifica que todo cuadre.
 */
class ReporteCierreCaja
{
    public static function datos(CierreCaja $cierre): array
    {
        $userId = (int) $cierre->user_id;
        $fecha = $cierre->fecha->toDateString();

        $movimientos = CierreCajaController::ventasDelDia($userId, $fecha)
            ->with(['paciente:id,nombre_completo', 'detalles:id,venta_id,producto_id,nombre,cantidad,precio,precio_original,total',
                'categoriaGasto:id,nombre', 'subcategoriaGasto:id,nombre'])
            ->get()
            ->sortBy(fn (Venta $v) => ($v->fecha_hora_cobro ?? $v->fecha_hora)?->format('Y-m-d H:i:s').sprintf('%010d', $v->id))
            ->values();
        $ingresos = $movimientos->reject(fn (Venta $v) => $v->esEgreso())->values();
        $gastos = $movimientos->filter(fn (Venta $v) => $v->esEgreso())->values();
        $reparto = $movimientos->mapWithKeys(fn (Venta $v) => [$v->id => ArqueoCaja::repartoDe($v)]);

        $arqueo = self::arqueo($cierre);
        $porFormaDePago = self::porFormaDePago($ingresos, $reparto);
        $porCategoria = self::porCategoria($ingresos);
        $porTipoPaciente = $ingresos->groupBy(fn (Venta $v) => $v->tipo_paciente ?: 'SIN DATO')
            ->map(fn ($grupo, $tipo) => ['nombre' => $tipo, 'cantidad' => $grupo->count(), 'total' => self::suma($grupo, 'total')])
            ->sortByDesc('total')->values()->all();
        $idsInternacion = DB::table('internaciones')->whereIn('venta_id', $ingresos->pluck('id'))->pluck('venta_id')->all();
        $internaciones = $ingresos->whereIn('id', $idsInternacion);
        $pendientesCobradas = $ingresos->filter(fn (Venta $v) => $v->estado === 'PENDIENTE'
            && ((int) $v->user_id !== $userId || $v->fecha_hora?->toDateString() !== $fecha));
        $totalVentas = self::suma($ingresos, 'total');
        $totalGastos = self::suma($gastos, 'total');

        $gastosLista = $gastos->map(fn (Venta $g) => [
            'id' => $g->id,
            'hora' => $g->fecha_hora?->format('H:i'),
            'descripcion' => $g->detalles->first()?->nombre ?: ($g->comentario ?: 'GASTO'),
            'categoria' => $g->categoriaGasto?->nombre ?: 'SIN CATEGORÍA',
            'subcategoria' => $g->subcategoriaGasto?->nombre,
            'efectivo' => $reparto[$g->id]['efectivo'],
            'total' => round((float) $g->total, 2),
        ])->values();

        $detalle = $movimientos->map(fn (Venta $v) => [
            'id' => $v->id,
            'hora' => ($v->fecha_hora_cobro ?? $v->fecha_hora)?->format('H:i'),
            'tipo' => $v->esEgreso() ? 'GASTO' : 'VENTA',
            'cliente' => $v->paciente?->nombre_completo ?: ($v->cliente ?: ($v->esEgreso() ? 'GASTO DE CAJA' : 'SIN CLIENTE')),
            'items' => $v->detalles->map(fn ($d) => [
                'nombre' => $d->nombre,
                'cantidad' => (float) $d->cantidad,
                'precio' => round((float) $d->precio, 2),
                'subtotal' => round((float) $d->total, 2),
            ])->values()->all(),
            'tipo_pago' => $v->tipo_pago ?: '—',
            'efectivo' => $reparto[$v->id]['efectivo'],
            'qr' => $reparto[$v->id]['qr'],
            'otros' => $reparto[$v->id]['otros'],
            'estado' => $v->estado,
            'total' => round(($v->esEgreso() ? -1 : 1) * (float) $v->total, 2),
        ])->values();

        $efectivoFormas = round(collect($porFormaDePago['filas'])->whereIn('clave', ['EFECTIVO', 'MIXTO_EFECTIVO'])->sum('total'), 2);
        $qrFormas = round(collect($porFormaDePago['filas'])->whereIn('clave', ['QR', 'MIXTO_QR'])->sum('total'), 2);
        $cuadres = [
            ['texto' => 'Formas de pago = total de ventas', 'ok' => self::igual($porFormaDePago['total'], $totalVentas)],
            ['texto' => 'Categorías = total de ventas', 'ok' => self::igual(collect($porCategoria)->sum('total'), $totalVentas)],
            ['texto' => 'Tipos de paciente = total de ventas', 'ok' => self::igual(collect($porTipoPaciente)->sum('total'), $totalVentas)],
            ['texto' => 'Efectivo de las ventas - gastos = efectivo esperado', 'ok' => self::igual($efectivoFormas - $totalGastos, $arqueo['efectivo_esperado'])],
            ['texto' => 'QR de las formas de pago = total QR', 'ok' => self::igual($qrFormas, $arqueo['qr_total'])],
            ['texto' => 'Detalle de movimientos = ventas - gastos', 'ok' => self::igual($detalle->sum('total'), $totalVentas - $totalGastos)],
        ];

        return [
            'encabezado' => [
                'cierre_id' => $cierre->id,
                'cajero' => $cierre->user?->name,
                'fecha' => $fecha,
                'cerrado_en' => $cierre->fecha_hora?->format('Y-m-d H:i'),
                'corregido_en' => $cierre->modificado_en?->format('Y-m-d H:i'),
                'estado' => $cierre->validado ? 'VALIDADO' : 'SIN VALIDAR',
                'validado_por' => $cierre->validadoPor?->name,
                'validado_en' => $cierre->validado_en?->format('Y-m-d H:i'),
                'autorizado_por' => $cierre->autorizadoPor?->name,
                'autorizado_hasta' => $cierre->autorizado_hasta?->format('Y-m-d H:i'),
                'comentario' => $cierre->comentario,
                'generado_en' => now()->format('Y-m-d H:i'),
            ],
            'arqueo' => $arqueo,
            'otros_medios' => [
                'qr_total' => $arqueo['qr_total'],
                'qr_cantidad' => $arqueo['qr_cantidad'],
                'otros' => $ingresos->filter(fn (Venta $v) => $reparto[$v->id]['otros'] > 0)
                    ->groupBy(fn (Venta $v) => mb_strtoupper((string) $v->tipo_pago))
                    ->map(fn ($grupo, $medio) => ['medio' => $medio, 'cantidad' => $grupo->count(),
                        'total' => round($grupo->sum(fn ($v) => $reparto[$v->id]['otros']), 2)])
                    ->values()->all(),
            ],
            'ventas' => [
                'por_forma_de_pago' => $porFormaDePago,
                'por_categoria' => $porCategoria,
                'por_tipo_paciente' => $porTipoPaciente,
                'internaciones' => ['cantidad' => $internaciones->count(), 'total' => self::suma($internaciones, 'total')],
                'pendientes_otros_dias' => ['cantidad' => $pendientesCobradas->count(), 'total' => self::suma($pendientesCobradas, 'total')],
                'cantidad' => $ingresos->count(),
                'total' => $totalVentas,
            ],
            'gastos' => [
                'lista' => $gastosLista->all(),
                'por_categoria' => $gastosLista->groupBy('categoria')
                    ->map(fn ($grupo, $categoria) => ['categoria' => $categoria, 'cantidad' => $grupo->count(), 'total' => round($grupo->sum('total'), 2)])
                    ->values()->all(),
                'cantidad' => $gastos->count(),
                'total' => $totalGastos,
            ],
            'controles' => [
                'anuladas' => self::anuladas($cierre),
                'precio_modificado' => self::preciosModificados($ingresos),
                'pendientes_sin_cobrar' => self::pendientesSinCobrar($cierre),
            ],
            'detalle' => $detalle->all(),
            'firmas' => ['cajero' => $cierre->user?->name, 'supervisor' => $cierre->validadoPor?->name],
            'cuadres' => $cuadres,
            'cuadra' => collect($cuadres)->every('ok'),
        ];
    }

    /** Arqueo de efectivo: guardado en los cierres nuevos; al vuelo en los anteriores. */
    private static function arqueo(CierreCaja $cierre): array
    {
        $anterior = $cierre->formula === null;
        $cifras = $anterior
            ? ArqueoCaja::calcular(CierreCajaController::ventasDelDia($cierre->user_id, $cierre->fecha->toDateString()))
            : $cierre->only(['ingresos_total', 'efectivo_ingresos', 'qr_total', 'qr_cantidad', 'otros_total',
                'otros_cantidad', 'gastos_efectivo', 'gastos_cantidad', 'efectivo_esperado']);
        $cifras = collect($cifras)->map(fn ($valor, $campo) => str_ends_with($campo, '_cantidad') ? (int) $valor : round((float) $valor, 2))->all();
        $declarado = round((float) $cierre->monto, 2);

        $desglose = collect($cierre->detalle_efectivo ?? [])
            ->filter(fn ($cantidad) => (int) $cantidad > 0)
            ->map(fn ($cantidad, $corte) => ['corte' => (float) $corte, 'cantidad' => (int) $cantidad, 'subtotal' => round((float) $corte * (int) $cantidad, 2)])
            ->values()->all();

        return [
            ...$cifras,
            'declarado' => $declarado,
            'desglose' => $desglose,
            'diferencia_efectivo' => $anterior
                ? round($declarado - $cifras['efectivo_esperado'], 2)
                : round((float) $cierre->diferencia_efectivo, 2),
            'formula_anterior' => $anterior,
            // Cierre anterior: la diferencia que se registró al cerrar (contra el total, QR incluido).
            'diferencia_registrada' => $anterior ? round((float) $cierre->diferencia, 2) : null,
            'monto_sistema_registrado' => $anterior ? round((float) $cierre->monto_sistema, 2) : null,
            'nota' => $anterior
                ? 'Este cierre se hizo con la fórmula anterior, que comparaba el efectivo declarado con el total de las ventas (QR incluido). '
                    .'La diferencia de efectivo se recalculó con los movimientos actuales: si alguna venta se anuló después del cierre, '
                    .'puede no coincidir con lo que había en caja ese día. El cierre guardado no se modificó.'
                : null,
        ];
    }

    /** Ventas por forma de pago, con el mixto separado en sus dos partes. */
    private static function porFormaDePago(Collection $ingresos, Collection $reparto): array
    {
        $mixto = fn (Venta $v) => mb_strtoupper((string) $v->tipo_pago) === 'MIXTO';
        $fila = function (string $clave, string $nombre, callable $filtro, string $parte) use ($ingresos, $reparto) {
            $ventas = $ingresos->filter(fn (Venta $v) => $filtro($v) && $reparto[$v->id][$parte] > 0);

            return ['clave' => $clave, 'nombre' => $nombre, 'cantidad' => $ventas->count(),
                'total' => round($ventas->sum(fn ($v) => $reparto[$v->id][$parte]), 2)];
        };
        $filas = [
            $fila('EFECTIVO', 'Efectivo', fn ($v) => ! $mixto($v), 'efectivo'),
            $fila('QR', 'QR', fn ($v) => ! $mixto($v), 'qr'),
            $fila('MIXTO_EFECTIVO', 'Mixto: parte en efectivo', $mixto, 'efectivo'),
            $fila('MIXTO_QR', 'Mixto: parte QR', $mixto, 'qr'),
            $fila('OTROS', 'Transferencia / tarjeta', fn () => true, 'otros'),
        ];

        return ['filas' => $filas, 'total' => round(collect($filas)->sum('total'), 2)];
    }

    /** Ventas por categoría (tipo_producto_padres) de sus ítems; los ítems sin producto, aparte. */
    private static function porCategoria(Collection $ingresos): array
    {
        if ($ingresos->isEmpty()) {
            return [];
        }

        return DB::table('venta_detalles as d')
            ->leftJoin('productos as p', 'p.id', '=', 'd.producto_id')
            ->leftJoin('tipo_productos as t', 't.id', '=', 'p.tipo_producto_id')
            ->leftJoin('tipo_producto_padres as tp', 'tp.id', '=', 't.tipo_producto_padre_id')
            ->whereIn('d.venta_id', $ingresos->pluck('id'))
            ->whereNull('d.deleted_at')
            ->groupBy('tp.id', 'tp.nombre')
            ->selectRaw("COALESCE(tp.nombre, 'SIN CATEGORÍA (internación e ítems sin producto)') AS nombre, COUNT(DISTINCT d.venta_id) AS cantidad, SUM(d.total) AS total")
            ->orderByDesc('total')
            ->get()
            ->map(fn ($f) => ['nombre' => $f->nombre, 'cantidad' => (int) $f->cantidad, 'total' => round((float) $f->total, 2)])
            ->all();
    }

    /**
     * Ventas del día del cajero anuladas, antes o después del cierre. El momento
     * sale de anulado_en; para las anteriores a ese dato, de la auditoría y, si
     * no la hay, de la última modificación.
     */
    private static function anuladas(CierreCaja $cierre): array
    {
        return Venta::with(['anuladoPor:id,name', 'paciente:id,nombre_completo'])
            ->where('user_id', $cierre->user_id)
            ->where('tipo_movimiento', 'INGRESO')
            ->where('estado', 'ANULADO')
            ->whereDate('fecha_hora', $cierre->fecha->toDateString())
            ->orderBy('fecha_hora')
            ->get()
            ->map(function (Venta $v) use ($cierre) {
                [$momento, $fuente] = match (true) {
                    $v->anulado_en !== null => [$v->anulado_en, 'registro'],
                    default => self::momentoAuditado($v),
                };

                return [
                    'id' => $v->id,
                    'hora' => $v->fecha_hora?->format('H:i'),
                    'cliente' => $v->paciente?->nombre_completo ?: ($v->cliente ?: 'SIN CLIENTE'),
                    'total' => round((float) $v->total, 2),
                    'anulado_en' => $momento?->format('Y-m-d H:i'),
                    'anulado_por' => $v->anuladoPor?->name,
                    'fuente' => $fuente,
                    'despues_del_cierre' => $momento !== null && $cierre->fecha_hora !== null && $momento->gt($cierre->fecha_hora),
                ];
            })->all();
    }

    /** @return array{0: Carbon|null, 1: string} */
    private static function momentoAuditado(Venta $venta): array
    {
        $auditoria = Audit::where('auditable_type', Venta::class)
            ->where('auditable_id', $venta->id)
            ->where('event', 'updated')
            ->where('new_values', 'like', '%ANULADO%')
            ->orderBy('id')
            ->first();
        if ($auditoria) {
            return [$auditoria->created_at, 'auditoría'];
        }

        return [$venta->updated_at, 'última modificación'];
    }

    /** Ítems vendidos a un precio distinto del de lista. */
    private static function preciosModificados(Collection $ingresos): array
    {
        return $ingresos->flatMap(fn (Venta $v) => $v->detalles
            ->filter(fn ($d) => $d->precio_original !== null && abs((float) $d->precio - (float) $d->precio_original) >= 0.005)
            ->map(fn ($d) => [
                'venta_id' => $v->id,
                'item' => $d->nombre,
                'cantidad' => (float) $d->cantidad,
                'precio_lista' => round((float) $d->precio_original, 2),
                'precio_cobrado' => round((float) $d->precio, 2),
                'diferencia' => round(((float) $d->precio - (float) $d->precio_original) * (float) $d->cantidad, 2),
            ]))->values()->all();
    }

    /** Pendientes que el cajero registró ese día y no estaban cobradas al cerrar. */
    private static function pendientesSinCobrar(CierreCaja $cierre): array
    {
        return Venta::with('paciente:id,nombre_completo')
            ->where('user_id', $cierre->user_id)
            ->where('tipo_movimiento', 'INGRESO')
            ->where('estado', 'PENDIENTE')
            ->whereDate('fecha_hora', $cierre->fecha->toDateString())
            ->where(fn ($q) => $q->whereNull('fecha_hora_cobro')->orWhere('fecha_hora_cobro', '>', $cierre->fecha_hora))
            ->orderBy('fecha_hora')
            ->get()
            ->map(fn (Venta $v) => [
                'id' => $v->id,
                'hora' => $v->fecha_hora?->format('H:i'),
                'cliente' => $v->paciente?->nombre_completo ?: ($v->cliente ?: 'SIN CLIENTE'),
                'total' => round((float) $v->total, 2),
                'cobrada_despues' => $v->fecha_hora_cobro?->format('Y-m-d H:i'),
            ])->all();
    }

    private static function suma(Collection $ventas, string $campo): float
    {
        return round((float) $ventas->sum(fn ($v) => (float) $v->{$campo}), 2);
    }

    private static function igual($a, $b): bool
    {
        return abs(round((float) $a, 2) - round((float) $b, 2)) < 0.005;
    }
}
