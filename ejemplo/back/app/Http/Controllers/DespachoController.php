<?php

namespace App\Http\Controllers;

use App\Models\Despacho;
use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DespachoController extends Controller
{
    public function index(Request $request)
    {
        $query = Despacho::with(['user:id,name', 'pedido:id,nombre_usuario', 'unidad:id,nombre'])
            ->withCount('detalles');

        if ($request->filled('producto_id')) {
            $productoId = $request->input('producto_id');
            $query->whereHas('detalles', function ($query) use ($productoId) {
                $query->where('almacen_item_id', $productoId);
            })->with(['detalles' => function ($query) use ($productoId) {
                $query->where('almacen_item_id', $productoId);
            }]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha_entrega', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('fecha_entrega', '<=', $request->date_to);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($q2) use ($q) {
                $q2->where('nro', 'like', "%{$q}%")
                    ->orWhere('solicitante', 'like', "%{$q}%")
                    ->orWhere('servicio', 'like', "%{$q}%")
                    ->orWhere('personal_recepcion', 'like', "%{$q}%");
            });
        }

        $rowsPerPage = max(1, (int) $request->input('rowsPerPage', 15));

        $base = clone $query;
        $summary = [
            'total_bs' => (float) (clone $base)->where('estado', '!=', 'ANULADO')->sum('total'),
            'cantidad' => (int) (clone $base)->count(),
            'despachados' => (int) (clone $base)->where('estado', 'DESPACHADO')->count(),
            'anulados' => (int) (clone $base)->where('estado', 'ANULADO')->count(),
        ];

        $paginated = $query->orderBy('fecha_entrega', 'desc')->orderBy('id', 'desc')->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    public function show($id)
    {
        return Despacho::with([
            'user:id,name,firma,sello,mostrar_firma,mostrar_sello',
            'pedido:id,nombre_usuario,fecha_hora',
            'unidad:id,nombre',
            'detalles',
        ])->findOrFail($id);
    }

    public function pedidoLookup($id)
    {
        $pedido = Pedido::with(['user', 'unidad:id,nombre', 'detalles.producto:id,nombre,unidad_medida,imagen,subpartida_id'])
            ->find($id);

        if (! $pedido) {
            return response()->json(['message' => "No se encontró el pedido #{$id}"], 404);
        }

        if ($pedido->estado !== 'ACEPTADO') {
            return response()->json([
                'message' => "El pedido #{$id} está {$pedido->estado} y no puede despacharse. Solo se pueden despachar pedidos confirmados (ACEPTADO).",
            ], 422);
        }

        $yaDespachado = Despacho::where('pedido_id', $pedido->id)
            ->where('estado', '!=', 'ANULADO')
            ->exists();

        if ($yaDespachado) {
            return response()->json([
                'message' => "El pedido #{$id} ya fue despachado",
            ], 422);
        }

        $itemIds = $pedido->detalles->pluck('producto_id')->filter()->unique()->values();
        $stocks = $this->stockPorItem($itemIds);

        $detalles = $pedido->detalles->map(function ($d) use ($stocks) {
            return [
                'producto_id' => $d->producto_id,
                'nombre' => $d->producto?->nombre,
                'unidad' => $d->producto?->unidad_medida,
                'imagen' => $d->producto?->imagen,
                'cantidad_pedida' => (int) $d->cantidad,
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'stock_disponible' => (int) ($stocks[$d->producto_id] ?? 0),
            ];
        });

        return response()->json([
            'pedido' => [
                'id' => $pedido->id,
                'fecha_hora' => $pedido->fecha_hora,
                'nombre_usuario' => $pedido->nombre_usuario,
                'comentario' => $pedido->comentario,
                'estado' => $pedido->estado,
                'total' => (float) $pedido->total,
                'unidad_id' => $pedido->unidad_id,
                'unidad' => $pedido->unidad,
            ],
            'detalles' => $detalles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pedido_id' => 'required|exists:pedidos,id',
            'fecha_entrega' => 'nullable|date',
            'personal_recepcion' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.almacen_item_id' => 'nullable|exists:almacen_items,id',
            'items.*.descripcion' => 'required|string|max:500',
            'items.*.unidad' => 'nullable|string|max:100',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        $pedido = Pedido::findOrFail($data['pedido_id']);

        if ($pedido->estado !== 'ACEPTADO') {
            return response()->json(['message' => "El pedido está {$pedido->estado} y no puede despacharse. Solo se pueden despachar pedidos confirmados (ACEPTADO)."], 422);
        }

        $yaDespachado = Despacho::where('pedido_id', $pedido->id)
            ->where('estado', '!=', 'ANULADO')
            ->exists();
        if ($yaDespachado) {
            return response()->json(['message' => 'Este pedido ya fue despachado'], 422);
        }

        $itemIds = collect($data['items'])->pluck('almacen_item_id')->filter()->unique()->values();
        $stocks = $this->stockPorItem($itemIds);

        foreach ($data['items'] as $item) {
            $itemId = $item['almacen_item_id'] ?? null;
            if (! $itemId) {
                continue;
            }
            $disponible = (float) ($stocks[$itemId] ?? 0);
            if ((float) $item['cantidad'] > $disponible) {
                return response()->json([
                    'message' => "Stock insuficiente para '{$item['descripcion']}'. Disponible: {$disponible}, solicitado: {$item['cantidad']}",
                ], 422);
            }
        }

        return DB::transaction(function () use ($data, $pedido, $request) {
            $total = collect($data['items'])->sum(
                fn ($i) => (float) ($i['precio_unitario'] ?? 0) * (float) $i['cantidad']
            );

            $year = now()->year;
            $count = Despacho::whereYear('created_at', $year)->withTrashed()->count() + 1;
            $nro = 'DSP-'.str_pad($count, 4, '0', STR_PAD_LEFT).'/'.$year;

            $solicitante = $pedido->nombre_usuario ?? $pedido->user?->name;
            $servicio = $pedido->unidad;

            $despacho = Despacho::create([
                'pedido_id' => $pedido->id,
                'unidad_id' => $pedido->unidad_id,
                'user_id' => $request->user()->id,
                'nro' => $nro,
                'fecha_entrega' => $data['fecha_entrega'] ?? now(),
                'solicitante' => $solicitante,
                'servicio' => $servicio,
                'personal_recepcion' => $data['personal_recepcion'] ?? $solicitante,
                'estado' => 'DESPACHADO',
                'observaciones' => $data['observaciones'] ?? null,
                'total' => $total,
            ]);

            foreach ($data['items'] as $idx => $item) {
                $cantidad = (int) $item['cantidad'];
                $precioVenta = (float) ($item['precio_unitario'] ?? 0);
                $itemId = $item['almacen_item_id'] ?? null;
                $unidad = $item['unidad'] ?? null;

                if (! $itemId) {
                    $despacho->detalles()->create([
                        'almacen_item_id' => null,
                        'item' => $idx + 1,
                        'descripcion' => $item['descripcion'],
                        'unidad' => $unidad,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioVenta,
                        'total' => round($precioVenta * $cantidad, 2),
                    ]);

                    continue;
                }

                $asignaciones = $this->asignarPeps((int) $itemId, $cantidad);

                // Una fila consolidada en despacho_detalles (para el PDF)
                $detalle = $despacho->detalles()->create([
                    'almacen_item_id' => $itemId,
                    'compra_detalle_id' => count($asignaciones) === 1
                        ? $asignaciones[0]['compra_detalle_id']
                        : null,
                    'item' => $idx + 1,
                    'descripcion' => $item['descripcion'],
                    'unidad' => $unidad,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioVenta,
                    'total' => round($precioVenta * $cantidad, 2),
                    'lote' => count($asignaciones) === 1 ? $asignaciones[0]['lote'] : null,
                    'fecha_vencimiento' => count($asignaciones) === 1
                        ? $asignaciones[0]['fecha_vencimiento']
                        : null,
                ]);

                // Una fila por lote PEPS en despacho_detalle_reales (precio de compra, para contabilidad)
                foreach ($asignaciones as $asignacion) {
                    \App\Models\DespachoDetalleReal::create([
                        'despacho_id'        => $despacho->id,
                        'despacho_detalle_id' => $detalle->id,
                        'almacen_item_id'    => $itemId,
                        'compra_detalle_id'  => $asignacion['compra_detalle_id'],
                        'item'               => $idx + 1,
                        'unidad'             => $unidad,
                        'cantidad'           => $asignacion['cantidad'],
                        'precio_unitario'    => $asignacion['precio'],
                        'total'              => round((float) $asignacion['precio'] * $asignacion['cantidad'], 2),
                        'lote'               => $asignacion['lote'],
                        'fecha_vencimiento'  => $asignacion['fecha_vencimiento'],
                    ]);
                }
            }

            if ($pedido->estado === 'PENDIENTE') {
                $pedido->update(['estado' => 'ACEPTADO']);
            }

            return response()->json($despacho->load(['user:id,name', 'detalles', 'pedido:id,nombre_usuario,fecha_hora']), 201);
        });
    }

    public function cambiarUnidad(Request $request, $id)
    {
        $despacho = Despacho::findOrFail($id);

        $request->validate(['unidad_id' => 'required|exists:unidades,id']);

        $unidad = \App\Models\Unidad::findOrFail($request->unidad_id);

        $despacho->update([
            'unidad_id' => $unidad->id,
            'servicio'  => $unidad->nombre,
        ]);

        return response()->json($despacho->load([
            'user:id,name',
            'unidad:id,nombre',
            'pedido:id,nombre_usuario,fecha_hora',
            'detalles',
        ]));
    }

    public function anular($id)
    {
        $despacho = Despacho::with('detalles')->findOrFail($id);
        if ($despacho->estado === 'ANULADO') {
            return response()->json(['message' => 'El despacho ya está anulado'], 422);
        }

        return DB::transaction(function () use ($despacho) {
            foreach ($despacho->detalles()->with('reales')->get() as $detalle) {
                if ($detalle->reales->isNotEmpty()) {
                    // Estructura nueva: restaurar por cada lote real y soft-delete
                    foreach ($detalle->reales as $real) {
                        if (! $real->compra_detalle_id) {
                            continue;
                        }
                        DB::table('compra_detalles')
                            ->where('id', $real->compra_detalle_id)
                            ->update([
                                'cantidad_venta' => DB::raw('GREATEST(COALESCE(cantidad_venta, 0) - '.(int) $real->cantidad.', 0)'),
                            ]);
                        $real->delete();
                    }
                } elseif ($detalle->compra_detalle_id) {
                    // Estructura antigua (compat): restaurar desde el propio detalle
                    DB::table('compra_detalles')
                        ->where('id', $detalle->compra_detalle_id)
                        ->update([
                            'cantidad_venta' => DB::raw('GREATEST(COALESCE(cantidad_venta, 0) - '.(int) $detalle->cantidad.', 0)'),
                        ]);
                }
            }

            $despacho->update(['estado' => 'ANULADO']);

            return response()->json($despacho->load(['user:id,name', 'detalles']));
        });
    }

    public function destroy($id)
    {
        return $this->anular($id);
    }

    public function printPdf($id)
    {
        $despacho = Despacho::with([
            'user:id,name,firma,sello,mostrar_firma,mostrar_sello',
            'pedido:id,nombre_usuario,fecha_hora',
            'detalles',
        ])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.despacho_detalle', [
            'despacho' => $despacho,
        ])->setPaper('letter', 'portrait');

        $filename = 'despacho_'.str_replace(['/', '\\'], '-', $despacho->nro ?? $despacho->id).'.pdf';

        return $pdf->stream($filename);
    }

    private function stockPorItem($itemIds)
    {
        $itemIds = collect($itemIds)->filter()->unique()->values();
        if ($itemIds->isEmpty()) {
            return [];
        }

        $compras = DB::table('compra_detalles')
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->select('producto_id', DB::raw("COALESCE(SUM(CASE WHEN compras.tipo_registro = 'SALIDA' THEN -COALESCE(compra_detalles.cantidad, 0) ELSE COALESCE(compra_detalles.cantidad, 0) - COALESCE(compra_detalles.cantidad_venta, 0) END), 0) as total"))
            ->whereIn('producto_id', $itemIds)
            ->whereNull('compra_detalles.deleted_at')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->whereRaw("UPPER(COALESCE(compra_detalles.estado, '')) = 'ACTIVO'")
            ->groupBy('producto_id')
            ->pluck('total', 'producto_id');

        $despachos = DB::table('despacho_detalles as dd')
            ->join('despachos as d', 'dd.despacho_id', '=', 'd.id')
            ->select('dd.almacen_item_id', DB::raw('SUM(dd.cantidad) as total'))
            ->whereIn('dd.almacen_item_id', $itemIds)
            ->whereNull('dd.compra_detalle_id')
            ->whereNull('dd.deleted_at')
            ->where('d.estado', '!=', 'ANULADO')
            ->whereNull('d.deleted_at')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('despacho_detalle_reales as ddr')
                    ->whereColumn('ddr.despacho_detalle_id', 'dd.id')
                    ->whereNull('ddr.deleted_at');
            })
            ->groupBy('dd.almacen_item_id')
            ->pluck('total', 'dd.almacen_item_id');

        $result = [];
        foreach ($itemIds as $id) {
            $result[$id] = (int) ($compras[$id] ?? 0) - (int) ($despachos[$id] ?? 0);
        }

        return $result;
    }

    private function asignarPeps(int $itemId, int $cantidad): array
    {
        $pendiente = $cantidad;
        $asignaciones = [];

        $lotes = DB::table('compra_detalles')
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->where('compra_detalles.producto_id', $itemId)
            ->whereNull('compra_detalles.deleted_at')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->where('compras.tipo_registro', '!=', 'SALIDA')
            ->whereRaw("UPPER(COALESCE(compra_detalles.estado, '')) = 'ACTIVO'")
            ->whereRaw('COALESCE(compra_detalles.cantidad, 0) > COALESCE(compra_detalles.cantidad_venta, 0)')
            ->orderBy('compras.fecha_hora')
            ->orderBy('compras.id')
            ->orderBy('compra_detalles.id')
            ->lockForUpdate()
            ->select([
                'compra_detalles.id',
                'compra_detalles.cantidad',
                'compra_detalles.cantidad_venta',
                'compra_detalles.precio',
                'compra_detalles.lote',
                'compra_detalles.fecha_vencimiento',
            ])
            ->get();

        foreach ($lotes as $lote) {
            if ($pendiente <= 0) {
                break;
            }

            $disponible = (int) $lote->cantidad - (int) $lote->cantidad_venta;
            if ($disponible <= 0) {
                continue;
            }

            $salida = min($pendiente, $disponible);

            DB::table('compra_detalles')
                ->where('id', $lote->id)
                ->update([
                    'cantidad_venta' => DB::raw('COALESCE(cantidad_venta, 0) + '.$salida),
                ]);

            $asignaciones[] = [
                'compra_detalle_id' => $lote->id,
                'cantidad'          => $salida,
                'precio'            => (float) ($lote->precio ?? 0),
                'lote'              => $lote->lote,
                'fecha_vencimiento' => $lote->fecha_vencimiento,
            ];

            $pendiente -= $salida;
        }

        if ($pendiente > 0) {
            abort(response()->json(['message' => 'Stock insuficiente al aplicar PEPS'], 422));
        }

        return $asignaciones;
    }
}
