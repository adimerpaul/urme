<?php

namespace App\Http\Controllers;

use App\Exports\CierreCajaVentasExport;
use App\Models\CierreCaja;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Cierre de caja diario por usuario.
 *
 * Reglas del negocio:
 *  - Un solo cierre por usuario y por día (índice único en la tabla).
 *  - Volver a cerrar el mismo día no recalcula nada: devuelve el cierre guardado
 *    con el mismo monto.
 *  - El cierre admite una sola corrección, hecha por el mismo usuario que cerró.
 *  - Con la caja cerrada, ese usuario ya no puede registrar ventas ese día.
 */
class CierreCajaController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Cierres Caja');

        $query = CierreCaja::with('user:id,name,username')
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

        $cierres = $query->paginate((int) $request->input('per_page', 15));

        // Sin 'Ver Montos Caja' solo se ve lo declarado, nunca el sistema ni la diferencia.
        $cierres->getCollection()->transform(fn ($cierre) => $this->ocultarMontos($cierre, $request->user()));

        return response()->json($cierres);
    }

    /**
     * Estado de la caja de hoy del usuario conectado.
     *
     * El cierre es a ciegas: el total del día TODAVÍA ABIERTO solo viaja a quien
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
        $totales = $verMontos ? $this->totalesDelDia($user->id, $fecha) : null;

        return response()->json([
            'fecha' => $fecha,
            'cerrada' => (bool) $cierre,
            'ver_montos' => $verMontos,
            'cierre' => $this->ocultarMontos($cierre?->load('user:id,name,username'), $user),
            'total_sistema' => $totales['total'] ?? null,
            'cantidad_ventas' => $totales['cantidad'] ?? null,
        ]);
    }

    public function store(Request $request)
    {
        $this->req($request, 'Cerrar Caja');

        $request->validate([
            'monto' => 'required|numeric|min:0',
            'comentario' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $fecha = $this->hoy();

        // Si ya cerró hoy, se devuelve el mismo cierre con el mismo monto.
        if ($cierre = self::cierreDelDia($user->id, $fecha)) {
            return response()->json([
                'message' => 'La caja de hoy ya fue cerrada',
                'cierre' => $this->ocultarMontos($cierre->load('user:id,name,username'), $user),
                'ya_existia' => true,
            ]);
        }

        $totales = $this->totalesDelDia($user->id, $fecha);
        $monto = round((float) $request->monto, 2);

        $cierre = CierreCaja::create([
            'user_id' => $user->id,
            'fecha' => $fecha,
            'monto_sistema' => $totales['total'],
            'monto' => $monto,
            'diferencia' => round($monto - $totales['total'], 2),
            'cantidad_ventas' => $totales['cantidad'],
            'fecha_hora' => now(),
            'comentario' => $request->comentario ?: null,
        ]);

        return response()->json([
            'message' => 'Caja cerrada',
            'cierre' => $this->ocultarMontos($cierre->load('user:id,name,username'), $user),
            'ya_existia' => false,
        ], 201);
    }

    /** Única corrección permitida, y solo por el usuario que cerró. */
    public function update(Request $request, $id)
    {
        $this->req($request, 'Cerrar Caja');

        $request->validate([
            'monto' => 'required|numeric|min:0',
            'comentario' => 'nullable|string|max:500',
        ]);

        $cierre = CierreCaja::findOrFail($id);
        $user = $request->user();

        if ((int) $cierre->user_id !== (int) $user->id) {
            abort(403, 'Solo el usuario que cerró la caja puede modificar el cierre');
        }
        if (! $cierre->puede_modificar) {
            abort(422, 'Este cierre ya fue modificado una vez y no admite más cambios');
        }

        $monto = round((float) $request->monto, 2);
        $cierre->update([
            'monto' => $monto,
            'diferencia' => round($monto - (float) $cierre->monto_sistema, 2),
            'comentario' => $request->comentario ?: null,
            'modificado_en' => now(),
        ]);

        return response()->json([
            'message' => 'Cierre modificado',
            'cierre' => $this->ocultarMontos($cierre->load('user:id,name,username'), $user),
        ]);
    }

    /** Ventas que componen un cierre ya guardado (las del usuario en esa fecha). */
    public function ventas(Request $request, $id)
    {
        $this->req($request, 'Ver Cierres Caja');

        $cierre = CierreCaja::with('user:id,name,username')->findOrFail($id);

        $ventas = self::ventasDelDia($cierre->user_id, $cierre->fecha->toDateString())
            ->with([
                'paciente:id,nombre_completo,ci',
                'user:id,name',
                'cobradoPor:id,name',
                'detalles:id,venta_id,nombre,cantidad,precio,total',
            ])
            ->orderBy('fecha_hora')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return response()->json([
            'cierre' => $this->ocultarMontos($cierre, $request->user()),
            'ventas' => $ventas,
        ]);
    }

    public function ventasExportExcel(Request $request, $id)
    {
        $this->req($request, 'Ver Cierres Caja');

        $cierre = CierreCaja::with('user:id,name,username')->findOrFail($id);

        return Excel::download(
            new CierreCajaVentasExport($cierre),
            'cierre_caja_'.$cierre->fecha->format('Ymd').'_'.$cierre->id.'.xlsx'
        );
    }

    public function ventasExportPdf(Request $request, $id)
    {
        $this->req($request, 'Ver Cierres Caja');
        ini_set('memory_limit', '1024M');

        $cierre = CierreCaja::with('user:id,name,username')->findOrFail($id);

        $ventas = self::ventasDelDia($cierre->user_id, $cierre->fecha->toDateString())
            ->with(['paciente:id,nombre_completo,ci', 'detalles:id,venta_id,nombre,cantidad,precio,total'])
            ->orderBy('fecha_hora')
            ->get();

        $pdf = Pdf::loadView('reportes.cierre-caja-ventas', [
            'cierre' => $cierre,
            'ventas' => $ventas,
        ])->setPaper('letter');

        return $pdf->stream('cierre_caja_'.$cierre->fecha->format('Ymd').'_'.$cierre->id.'.pdf');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Sin 'Ver Montos Caja' el cierre viaja sin el total del sistema ni la
     * diferencia: la cajera solo ve el efectivo que ella misma declaró.
     */
    private function ocultarMontos(?CierreCaja $cierre, $user): ?CierreCaja
    {
        if ($cierre && ! $user->can('Ver Montos Caja')) {
            $cierre->makeHidden(['monto_sistema', 'diferencia']);
        }

        return $cierre;
    }

    /** Cierre vigente de un usuario en una fecha, o null. */
    public static function cierreDelDia(int $userId, string $fecha): ?CierreCaja
    {
        return CierreCaja::where('user_id', $userId)->whereDate('fecha', $fecha)->first();
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

    /** Ventas ACTIVO del usuario en el día. */
    private function totalesDelDia(int $userId, string $fecha): array
    {
        $ventas = self::ventasDelDia($userId, $fecha);

        return [
            'total' => round((float) (clone $ventas)->sum('total'), 2),
            'cantidad' => (clone $ventas)->count(),
        ];
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
