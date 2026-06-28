<?php

namespace App\Http\Controllers;

use App\Models\AlmacenItem;
use App\Models\Compra;
use App\Models\Unidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with([
            'proveedor:id,nombre,carnet',
            'user:id,name',
            'detalles:id,compra_id,nombre,cantidad',
        ])
            ->withCount('detalles')
            ->withSum('detalles as vendido_total', 'cantidad_venta')
            ->withCount(['despachoDetalleReales as despachado_count' => function ($q) {
                $q->whereHas('despacho', fn ($d) => $d->where('estado', '!=', 'ANULADO'));
            }]);

        $this->applyFilters($query, $request);

        $rowsPerPage = (int) $request->input('rowsPerPage', 15);
        $rowsPerPage = $rowsPerPage > 0 ? $rowsPerPage : 15;

        $summaryQuery = Compra::query();
        $this->applyFilters($summaryQuery, $request);
        $summary = [
            'total_compras' => (float) (clone $summaryQuery)->where('estado', 'ACTIVO')->sum('total'),
            'total_anuladas' => (float) (clone $summaryQuery)->where('estado', 'ANULADO')->sum('total'),
            'cantidad' => (int) (clone $summaryQuery)->count(),
        ];

        $paginated = $query
            ->orderBy('fecha_hora', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    public function show($id)
    {
        return Compra::with(['proveedor', 'unidad', 'user:id,name', 'detalles.producto'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero' => 'nullable|string|max:100',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'unidad_id' => 'nullable|exists:unidades,id',
            'fecha_hora' => 'nullable|date',
            'tipo_registro' => ['required', Rule::in(['ENTRADA', 'SALIDA'])],
            'motivo_registro' => 'required|string|max:50',
            'carnet' => 'nullable|string|max:100',
            'nombre' => 'nullable|string|max:255',
            'comentario' => 'nullable|string|max:500',
            'tipo_pago' => 'nullable|string|max:50',
            'nro_factura' => 'nullable|string|max:255',
            'categoria_programatica' => 'nullable|string|max:255',
            'orden_de_compra' => 'nullable|string|max:255',
            'codigo_interno' => 'nullable|string|max:255',
            'hoja_de_ruta' => 'nullable|string',
            'retencion_porcentaje' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:almacen_items,id',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio' => 'nullable|numeric|min:0',
            'items.*.factor' => 'nullable|numeric|min:0',
            'items.*.precio_venta' => 'nullable|numeric|min:0',
            'items.*.lote' => 'nullable|string|max:255',
            'items.*.fecha_vencimiento' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $productos = AlmacenItem::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');
            $total = collect($data['items'])->sum(fn ($item) => (float) ($item['precio'] ?? 0) * (float) $item['cantidad']);

            $compra = Compra::create([
                'user_id' => $request->user()->id,
                'numero' => $data['numero'] ?? null,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'unidad_id' => $data['unidad_id'] ?? null,
                'fecha_hora' => $data['fecha_hora'] ?? now(),
                'tipo_registro' => $data['tipo_registro'],
                'motivo_registro' => strtoupper($data['motivo_registro']),
                'carnet' => $data['carnet'] ?? null,
                'nombre' => $data['nombre'] ?? null,
                'comentario' => $data['comentario'] ?? null,
                'estado' => 'ACTIVO',
                'total' => $total,
                'retencion_porcentaje' => $data['retencion_porcentaje'] ?? 0,
                'tipo_pago' => $data['tipo_pago'] ?? 'EFECTIVO',
                'nro_factura' => $data['nro_factura'] ?? null,
                'categoria_programatica' => $data['categoria_programatica'] ?? null,
                'orden_de_compra' => $data['orden_de_compra'] ?? null,
                'codigo_interno' => $data['codigo_interno'] ?? null,
                'hoja_de_ruta' => $data['hoja_de_ruta'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $producto = $productos[$item['producto_id']];
                $precio = (float) ($item['precio'] ?? $producto->precio_unitario ?? 0);
                $cantidad = (float) $item['cantidad'];
                $factor = (float) ($item['factor'] ?? 1.25);
                $precio13 = round($precio * $factor, 2);

                $compra->detalles()->create([
                    'user_id' => $request->user()->id,
                    'proveedor_id' => $data['proveedor_id'] ?? null,
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                    'cantidad_venta' => 0,
                    'total' => round($precio * $cantidad, 2),
                    'factor' => $factor,
                    'precio13' => $precio13,
                    'total13' => round($precio13 * $cantidad, 2),
                    'precio_venta' => $item['precio_venta'] ?? $precio13,
                    'estado' => 'ACTIVO',
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                    'nro_factura' => $data['nro_factura'] ?? null,
                ]);

                if ($data['tipo_registro'] === 'ENTRADA' && $precio > 0) {
                    $producto->update(['precio_unitario' => $precio]);
                }
            }

            return response()->json($compra->load(['proveedor', 'user:id,name', 'detalles.producto']), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $compra = Compra::with('detalles')->findOrFail($id);

        if ($compra->created_at->lt(now()->subMonth())) {
            return response()->json([
                'message' => 'No se puede modificar esta compra: tiene más de un mes de antigüedad.',
            ], 403);
        }

        $data = $request->validate([
            'numero' => 'nullable|string|max:100',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'unidad_id' => 'nullable|exists:unidades,id',
            'fecha_hora' => 'nullable|date',
            'tipo_registro' => ['required', Rule::in(['ENTRADA', 'SALIDA'])],
            'motivo_registro' => 'required|string|max:50',
            'carnet' => 'nullable|string|max:100',
            'nombre' => 'nullable|string|max:255',
            'comentario' => 'nullable|string|max:500',
            'tipo_pago' => 'nullable|string|max:50',
            'nro_factura' => 'nullable|string|max:255',
            'categoria_programatica' => 'nullable|string|max:255',
            'orden_de_compra' => 'nullable|string|max:255',
            'codigo_interno' => 'nullable|string|max:255',
            'hoja_de_ruta' => 'nullable|string',
            'retencion_porcentaje' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:almacen_items,id',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio' => 'nullable|numeric|min:0',
            'items.*.factor' => 'nullable|numeric|min:0',
            'items.*.precio_venta' => 'nullable|numeric|min:0',
            'items.*.lote' => 'nullable|string|max:255',
            'items.*.fecha_vencimiento' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($compra, $data, $request) {
            $productos = AlmacenItem::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');
            $total = collect($data['items'])->sum(fn ($item) => (float) ($item['precio'] ?? 0) * (float) $item['cantidad']);

            // Preservar cantidad_venta existente por producto_id para no perder el tracking de despachos
            $cantidadesVenta = $compra->detalles->keyBy('producto_id')->map(fn ($d) => (float) $d->cantidad_venta);

            $compra->update([
                'numero' => $data['numero'] ?? null,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'unidad_id' => $data['unidad_id'] ?? null,
                'fecha_hora' => $data['fecha_hora'] ?? $compra->fecha_hora,
                'tipo_registro' => $data['tipo_registro'],
                'motivo_registro' => strtoupper($data['motivo_registro']),
                'carnet' => $data['carnet'] ?? null,
                'nombre' => $data['nombre'] ?? null,
                'comentario' => $data['comentario'] ?? $compra->comentario,
                'total' => $total,
                'retencion_porcentaje' => $data['retencion_porcentaje'] ?? 0,
                'tipo_pago' => $data['tipo_pago'] ?? 'EFECTIVO',
                'nro_factura' => $data['nro_factura'] ?? null,
                'categoria_programatica' => $data['categoria_programatica'] ?? null,
                'orden_de_compra' => $data['orden_de_compra'] ?? null,
                'codigo_interno' => $data['codigo_interno'] ?? null,
                'hoja_de_ruta' => $data['hoja_de_ruta'] ?? null,
            ]);

            $compra->detalles()->delete();

            foreach ($data['items'] as $item) {
                $producto = $productos[$item['producto_id']];
                $precio = (float) ($item['precio'] ?? $producto->precio_unitario ?? 0);
                $cantidad = (float) $item['cantidad'];
                $factor = (float) ($item['factor'] ?? 1.25);
                $precio13 = round($precio * $factor, 2);

                $compra->detalles()->create([
                    'user_id' => $request->user()->id,
                    'proveedor_id' => $data['proveedor_id'] ?? null,
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                    'cantidad_venta' => $cantidadesVenta[$producto->id] ?? 0,
                    'total' => round($precio * $cantidad, 2),
                    'factor' => $factor,
                    'precio13' => $precio13,
                    'total13' => round($precio13 * $cantidad, 2),
                    'precio_venta' => $item['precio_venta'] ?? $precio13,
                    'estado' => 'ACTIVO',
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                    'nro_factura' => $data['nro_factura'] ?? null,
                ]);

                if ($data['tipo_registro'] === 'ENTRADA' && $precio > 0) {
                    $producto->update(['precio_unitario' => $precio]);
                }
            }

            return response()->json($compra->load(['proveedor', 'user:id,name', 'detalles.producto']));
        });
    }

    public function printPdf($id)
    {
        //        if (auth()->check() && method_exists(auth()->user(), 'can') && !auth()->user()->can('Imprimir Compras')) {
        //            abort(403, 'No autorizado para imprimir esta compra');
        //        }

        $compra = Compra::with(['proveedor', 'unidad', 'user:id,name,firma,sello,mostrar_firma,mostrar_sello', 'detalles.producto.subpartida'])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.compra_detalle', [
            'compra' => $compra,
        ])->setPaper('letter', 'portrait');

        $filename = 'compra_'.$compra->id.'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($filename);
    }

    public function destroy($id)
    {
        $compra = Compra::with('detalles')->findOrFail($id);

        $vendido = (float) $compra->detalles->sum('cantidad_venta');
        if ($vendido > 0) {
            return response()->json([
                'message' => 'No se puede anular: ya se vendieron productos de esta compra.',
            ], 422);
        }

        $detalleIds = $compra->detalles->pluck('id');

        // Registros nuevos (con despacho_detalle_reales)
        $enDespachoActivo = \App\Models\DespachoDetalleReal::whereIn('compra_detalle_id', $detalleIds)
            ->whereHas('despacho', fn ($q) => $q->where('estado', '!=', 'ANULADO'))
            ->exists();

        // Compatibilidad: despachos anteriores que aún usan compra_detalle_id en despacho_detalles
        if (! $enDespachoActivo) {
            $enDespachoActivo = \App\Models\DespachoDetalle::whereIn('compra_detalle_id', $detalleIds)
                ->whereHas('despacho', fn ($q) => $q->where('estado', '!=', 'ANULADO'))
                ->doesntHave('reales')
                ->exists();
        }

        if ($enDespachoActivo) {
            return response()->json([
                'message' => 'No se puede anular: productos de esta compra ya fueron despachados.',
            ], 422);
        }

        $compra->update(['estado' => 'ANULADO']);
        $compra->detalles()->update(['estado' => 'ANULADO']);

        return response()->json(['message' => 'Compra anulada correctamente']);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('producto_id')) {
            $productoId = $request->input('producto_id');
            $query->whereHas('detalles', function ($q) use ($productoId) {
                $q->where('producto_id', $productoId);
            })->with(['detalles' => function ($q) use ($productoId) {
                $q->where('producto_id', $productoId)
                    ->select(['id', 'compra_id', 'nombre', 'cantidad', 'cantidad_venta', 'precio', 'total', 'lote', 'fecha_vencimiento']);
            }]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha_hora', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('fecha_hora', '<=', $request->date_to);
        }

        if ($request->filled('tipo_registro')) {
            $query->where('tipo_registro', $request->tipo_registro);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('nro_factura', 'like', "%{$q}%")
                    ->orWhereHas('proveedor', fn ($query) => $query->where('nombre', 'like', "%{$q}%"));
            });
        }
    }
}
