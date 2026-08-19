<?php

namespace App\Http\Controllers;

use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Ventas');

        $fechaInicio = $request->input('fecha_inicio', '');
        $fechaFin = $request->input('fecha_fin', '');
        $horaInicio = $request->input('hora_inicio', '');
        $horaFin = $request->input('hora_fin', '');
        $pacienteId = $request->input('paciente_id', '');
        $userId = $request->input('user_id', '');
        $estado = $request->input('estado', '');
        $perPage = (int) $request->input('per_page', 15);

        $query = Venta::with([
            'paciente:id,nombre_completo,ci',
            'doctor:id,nombre',
            'seguro:id,nombre',
            'user:id,name',
            'cobradoPor:id,name',
            'detalles:id,venta_id,nombre,lote,precio,cantidad,total',
        ])
            ->withCount('detalles')
            ->orderByDesc('fecha_hora');

        $this->applyFiltros($query, $fechaInicio, $fechaFin, $pacienteId, $userId, $estado, $horaInicio, $horaFin);

        $resumenQuery = Venta::query();
        $this->applyFiltros($resumenQuery, $fechaInicio, $fechaFin, $pacienteId, $userId, '', $horaInicio, $horaFin);

        // Los montos acumulados de caja solo se envían a quien tiene el permiso.
        $verMontos = $request->user()->can('Ver Montos Caja');

        return response()->json([
            'ver_montos' => $verMontos,
            'resumen' => $verMontos ? [
                'total_ventas' => (clone $resumenQuery)->where(function ($query) {
                    $query->where('estado', 'ACTIVO')
                        ->orWhere(fn ($pendiente) => $pendiente->where('estado', 'PENDIENTE')->whereNotNull('fecha_hora_cobro'));
                })->sum('total'),
                'total_pendientes' => (clone $resumenQuery)->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->sum('total'),
                'total_anuladas' => (clone $resumenQuery)->where('estado', 'ANULADO')->sum('total'),
                'cantidad' => (clone $resumenQuery)->count(),
                'cantidad_pendientes' => (clone $resumenQuery)->where('estado', 'PENDIENTE')->whereNull('fecha_hora_cobro')->count(),
            ] : null,
            'ventas' => $query->paginate($perPage),
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Ventas');
        $venta = Venta::with(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'cobradoPor:id,name', 'detalles.producto:id,nombre,codigo'])
            ->findOrFail($id);

        return response()->json($venta);
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Ventas');

        // Con la caja del día ya cerrada, este usuario no registra más ventas.
        if (CierreCajaController::cierreDelDia($request->user()->id, now()->toDateString())) {
            abort(422, 'Su caja de hoy ya fue cerrada: no puede registrar más ventas hasta mañana');
        }

        $request->validate([
            'paciente_id' => 'nullable|exists:pacientes,id',
            'doctor_id' => 'nullable|exists:doctores,id',
            'seguro_id' => 'nullable|exists:seguros,id',
            'cliente' => 'nullable|string|max:255',
            'tipo_pago' => 'nullable|string|max:50',
            'comentario' => 'nullable|string|max:500',
            'pago' => 'nullable|numeric|min:0',
            //            'estado' => 'nullable|in:ACTIVO,PENDIENTE',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'nullable|exists:productos,id',
            'detalles.*.compra_detalle_id' => 'nullable|exists:compra_detalles,id',
            'detalles.*.lote' => 'nullable|string|max:255',
            'detalles.*.nombre' => 'required_without:detalles.*.producto_id|nullable|string|max:255',
            'detalles.*.precio' => 'required|numeric|min:0',
            'detalles.*.cantidad' => 'required|numeric|min:0.0001',
        ]);

        $estado = $request->estado === 'PENDIENTE' ? 'PENDIENTE' : 'ACTIVO';

        $venta = DB::transaction(function () use ($request, $estado) {
            $venta = Venta::create([
                'user_id' => $request->user()->id,
                'paciente_id' => $request->paciente_id ?: null,
                'doctor_id' => $request->doctor_id ?: null,
                'seguro_id' => $request->seguro_id ?: null,
                'cliente' => $request->cliente ?: null,
                // La fecha la pone el servidor (zona America/La_Paz): el front no la envía.
                'fecha_hora' => now(),
                'tipo_pago' => $request->tipo_pago ? mb_strtoupper($request->tipo_pago) : 'EFECTIVO',
                'comentario' => $request->comentario ?: null,
                'estado' => $estado,
                'total' => 0,
                'pago' => 0,
                'cambio' => 0,
            ]);

            $total = 0;
            foreach ($request->detalles as $item) {
                $producto = ! empty($item['producto_id'])
                    ? Producto::with('tipoProducto:id,nombre')->find($item['producto_id'])
                    : null;
                $precio = (float) $item['precio'];
                $cantidad = (float) $item['cantidad'];

                $loteCompra = null;
                $requiereLote = $producto
                    && mb_strtoupper($producto->tipoProducto?->nombre ?? '') === 'FARMACIA';
                if ($requiereLote) {
                    if (empty($item['compra_detalle_id']) || empty($item['lote'])) {
                        abort(422, "Debe seleccionar un lote para {$producto->nombre}");
                    }
                    $loteCompra = CompraDetalle::with('compra:id,estado')
                        ->lockForUpdate()
                        ->findOrFail($item['compra_detalle_id']);

                    if ((int) $loteCompra->producto_id !== (int) $producto->id
                        || $loteCompra->compra?->estado !== 'ACTIVO'
                        || ! $loteCompra->lote) {
                        abort(422, "El lote seleccionado para {$producto->nombre} no es válido");
                    }

                    $cantidadVendida = VentaDetalle::where('compra_detalle_id', $loteCompra->id)
                        ->whereHas('venta', fn ($query) => $query->where('estado', '<>', 'ANULADO'))
                        ->sum('cantidad');
                    $disponible = (float) $loteCompra->cantidad - (float) $cantidadVendida;

                    if ($cantidad > $disponible) {
                        abort(422, "Stock insuficiente en el lote {$loteCompra->lote} de {$producto->nombre}. Disponible: {$disponible}");
                    }
                }

                $lineaTotal = round($precio * $cantidad, 2);
                $total += $lineaTotal;

                VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $producto?->id,
                    'compra_detalle_id' => $loteCompra?->id,
                    'nombre' => mb_strtoupper($item['nombre'] ?? $producto?->nombre ?? ''),
                    'lote' => $loteCompra?->lote,
                    'fecha_vencimiento' => $loteCompra?->fecha_vencimiento,
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                    'total' => $lineaTotal,
                ]);
            }

            if ($estado === 'PENDIENTE') {
                $venta->update(['total' => $total]);

                return $venta;
            }

            $pago = (float) ($request->pago ?: $total);
            if ($pago < $total) {
                abort(422, 'El pago no puede ser menor al total de la venta');
            }

            $venta->update([
                'total' => $total,
                'pago' => $pago,
                'cambio' => round($pago - $total, 2),
            ]);

            return $venta;
        });

        return response()->json(
            $venta->load(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo']),
            201
        );
    }

    public function completar(Request $request, $id)
    {
        $this->req($request, 'Crear Ventas');

        // Cobrar una pendiente también mueve dinero: se bloquea igual que una venta nueva.
        if (CierreCajaController::cierreDelDia($request->user()->id, now()->toDateString())) {
            abort(422, 'Su caja de hoy ya fue cerrada: no puede cobrar ventas hasta mañana');
        }

        $request->validate(['pago' => 'nullable|numeric|min:0']);

        $venta = DB::transaction(function () use ($request, $id) {
            $venta = Venta::lockForUpdate()->findOrFail($id);

            if ($venta->estado !== 'PENDIENTE') {
                abort(422, 'Solo se pueden cobrar ventas pendientes');
            }
            if ($venta->fecha_hora_cobro) {
                abort(422, 'Esta venta pendiente ya fue cobrada');
            }

            $pago = (float) ($request->pago ?: $venta->total);
            if ($pago < (float) $venta->total) {
                abort(422, 'El pago no puede ser menor al total de la venta');
            }

            $venta->update([
                'cobrado_por_id' => $request->user()->id,
                'fecha_hora_cobro' => now(),
                'pago' => $pago,
                'cambio' => round($pago - (float) $venta->total, 2),
            ]);

            return $venta;
        });

        return response()->json(
            $venta->load(['paciente:id,nombre_completo,ci', 'doctor:id,nombre', 'seguro:id,nombre', 'user:id,name', 'cobradoPor:id,name', 'detalles.producto:id,nombre,codigo'])
        );
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Ventas');
        $venta = Venta::findOrFail($id);

        if ($venta->estado === 'ANULADO') {
            abort(422, 'La venta ya se encuentra anulada');
        }

        $venta->update(['estado' => 'ANULADO']);

        return response()->json(['message' => 'Venta anulada']);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function applyFiltros($query, $fechaInicio, $fechaFin, $pacienteId, $userId, $estado, $horaInicio = '', $horaFin = ''): void
    {
        if ($fechaInicio) {
            $query->where('fecha_hora', '>=', $fechaInicio.' '.($horaInicio ?: '00:00').':00');
        }
        if ($fechaFin) {
            $query->where('fecha_hora', '<=', $fechaFin.' '.($horaFin ?: '23:59').':59');
        }
        if ($pacienteId) {
            $query->where('paciente_id', $pacienteId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($estado) {
            $query->where('estado', $estado);
        }
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
