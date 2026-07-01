<?php

namespace App\Http\Controllers;

use App\Exports\ComprasExport;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Producto;
use App\Models\ProductoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $this->req($request, 'Ver Compras');

        $fechaInicio = $request->input('fecha_inicio', '');
        $fechaFin    = $request->input('fecha_fin', '');
        $proveedorId = $request->input('proveedor_id', '');
        $userId      = $request->input('user_id', '');
        $estado      = $request->input('estado', '');
        $perPage     = (int) $request->input('per_page', 15);

        $query = Compra::with(['proveedor:id,nombre', 'user:id,name'])
            ->withCount('detalles')
            ->orderByDesc('fecha_hora');

        $this->applyFiltros($query, $fechaInicio, $fechaFin, $proveedorId, $userId, $estado);

        $resumenQuery = Compra::query();
        $this->applyFiltros($resumenQuery, $fechaInicio, $fechaFin, $proveedorId, $userId, '');

        return response()->json([
            'resumen' => [
                'total_compras'  => (clone $resumenQuery)->where('estado', 'ACTIVO')->sum('total'),
                'total_anuladas' => (clone $resumenQuery)->where('estado', 'ANULADO')->sum('total'),
                'cantidad'       => (clone $resumenQuery)->count(),
            ],
            'compras' => $query->paginate($perPage),
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->req($request, 'Ver Compras');
        $compra = Compra::with(['proveedor:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo'])
            ->findOrFail($id);
        return response()->json($compra);
    }

    public function store(Request $request)
    {
        $this->req($request, 'Crear Compras');

        $request->validate([
            'proveedor_id'                    => 'nullable|exists:proveedores,id',
            'fecha_hora'                      => 'required|date',
            'nro_factura'                     => 'nullable|string|max:255',
            'tipo_pago'                       => 'nullable|string|max:50',
            'comentario'                      => 'nullable|string|max:500',
            'detalles'                        => 'required|array|min:1',
            'detalles.*.producto_id'          => 'nullable|exists:productos,id',
            'detalles.*.nombre'               => 'required_without:detalles.*.producto_id|nullable|string|max:255',
            'detalles.*.precio'               => 'required|numeric|min:0',
            'detalles.*.cantidad'             => 'required|numeric|min:0.0001',
            'detalles.*.factor'               => 'nullable|numeric|min:0',
            'detalles.*.precio_venta'         => 'nullable|numeric|min:0',
            'detalles.*.lote'                 => 'nullable|string|max:255',
            'detalles.*.fecha_vencimiento'    => 'nullable|date',
        ]);

        $compra = DB::transaction(function () use ($request) {
            $compra = Compra::create([
                'user_id'      => $request->user()->id,
                'proveedor_id' => $request->proveedor_id ?: null,
                'fecha_hora'   => $request->fecha_hora,
                'nro_factura'  => $request->nro_factura ?: null,
                'tipo_pago'    => $request->tipo_pago ? mb_strtoupper($request->tipo_pago) : 'EFECTIVO',
                'comentario'   => $request->comentario ?: null,
                'estado'       => 'ACTIVO',
                'total'        => 0,
            ]);

            $total = 0;
            foreach ($request->detalles as $item) {
                $producto = !empty($item['producto_id']) ? Producto::find($item['producto_id']) : null;
                $precio   = (float) $item['precio'];
                $cantidad = (float) $item['cantidad'];
                $lineaTotal = round($precio * $cantidad, 2);
                $total += $lineaTotal;

                $detalle = CompraDetalle::create([
                    'compra_id'         => $compra->id,
                    'producto_id'       => $producto?->id,
                    'nombre'            => mb_strtoupper($item['nombre'] ?? $producto?->nombre ?? ''),
                    'precio'            => $precio,
                    'cantidad'          => $cantidad,
                    'total'             => $lineaTotal,
                    'factor'            => $item['factor'] ?? null,
                    'precio_venta'      => $item['precio_venta'] ?? null,
                    'lote'              => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                ]);

                if ($producto) {
                    $this->aplicarStock($producto, $detalle, 1);

                    if (!empty($item['precio_venta'])) {
                        $producto->update(['precio' => $item['precio_venta']]);
                    }
                }
            }

            $compra->update(['total' => $total]);

            return $compra;
        });

        return response()->json(
            $compra->load(['proveedor:id,nombre', 'user:id,name', 'detalles.producto:id,nombre,codigo']),
            201
        );
    }

    public function destroy(Request $request, $id)
    {
        $this->req($request, 'Eliminar Compras');
        $compra = Compra::with('detalles.producto')->findOrFail($id);

        if ($compra->estado === 'ANULADO') {
            abort(422, 'La compra ya se encuentra anulada');
        }

        DB::transaction(function () use ($compra) {
            foreach ($compra->detalles as $detalle) {
                if ($detalle->producto) {
                    $this->aplicarStock($detalle->producto, $detalle, -1);
                }
            }
            $compra->update(['estado' => 'ANULADO']);
        });

        return response()->json(['message' => 'Compra anulada']);
    }

    public function exportExcel(Request $request)
    {
        $this->req($request, 'Ver Compras');
        $filters = $request->only(['fecha_inicio', 'fecha_fin', 'proveedor_id', 'user_id', 'estado']);
        return Excel::download(new ComprasExport($filters), 'compras_' . now()->format('Ymd_His') . '.xlsx');
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function applyFiltros($query, $fechaInicio, $fechaFin, $proveedorId, $userId, $estado): void
    {
        if ($fechaInicio) {
            $query->whereDate('fecha_hora', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('fecha_hora', '<=', $fechaFin);
        }
        if ($proveedorId) {
            $query->where('proveedor_id', $proveedorId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($estado) {
            $query->where('estado', $estado);
        }
    }

    /**
     * Aplica (signo 1) o revierte (signo -1) el impacto de una línea de compra
     * sobre el stock por lote en producto_inventarios.
     */
    private function aplicarStock(Producto $producto, CompraDetalle $detalle, int $signo): void
    {
        $inventario = ProductoInventario::where('producto_id', $producto->id)
            ->where('lote', $detalle->lote)
            ->first();

        if ($signo > 0) {
            if ($inventario) {
                $inventario->increment('cantidad_secundaria', $detalle->cantidad);
            } else {
                ProductoInventario::create([
                    'producto_id'          => $producto->id,
                    'responsable'          => auth()->user()?->name,
                    'lote'                 => $detalle->lote,
                    'cantidad_secundaria'  => $detalle->cantidad,
                    'unidad_secundaria_id' => $producto->unidad_id,
                    'origen_archivo'       => 'COMPRA #' . $detalle->compra_id,
                ]);
            }
            return;
        }

        if ($inventario) {
            $restante = max(0, (float) $inventario->cantidad_secundaria - (float) $detalle->cantidad);
            $inventario->update(['cantidad_secundaria' => $restante]);
        }
    }

    private function req(Request $request, string|array $permission): void
    {
        $user  = $request->user();
        $perms = is_array($permission) ? $permission : [$permission];
        foreach ($perms as $p) {
            if ($user->hasPermissionTo($p)) return;
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
