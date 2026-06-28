<?php

namespace App\Http\Controllers;

use App\Models\SolicitudSap;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SolicitudSapController extends Controller
{
    public function index(Request $request)
    {
        $query = SolicitudSap::with(['user:id,name'])->withCount('detalles');

        if ($request->filled('producto_id')) {
            $productoId = $request->input('producto_id');
            $query->whereHas('detalles', function ($q) use ($productoId) {
                $q->where('almacen_item_id', $productoId);
            })->with(['detalles' => function ($q) use ($productoId) {
                $q->where('almacen_item_id', $productoId);
            }]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('fecha', '<=', $request->date_to);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($q2) use ($q) {
                $q2->where('nro', 'like', "%{$q}%")
                    ->orWhere('unidad_solicitante', 'like', "%{$q}%")
                    ->orWhere('nro_cite', 'like', "%{$q}%");
            });
        }

        $rowsPerPage = max(1, (int) $request->input('rowsPerPage', 15));

        $baseQuery = clone $query;
        $summary = [
            'total_bs' => (float) (clone $baseQuery)->sum('total'),
            'cantidad' => (int) (clone $baseQuery)->count(),
            'pendiente' => (int) (clone $baseQuery)->where('estado', 'PENDIENTE')->count(),
            'aprobado' => (int) (clone $baseQuery)->where('estado', 'APROBADO')->count(),
        ];

        $paginated = $query->orderBy('fecha', 'desc')->orderBy('id', 'desc')->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    public function show($id)
    {
        return SolicitudSap::with(['user:id,name', 'detalles'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha' => 'required|date',
            'unidad_solicitante' => 'nullable|string|max:255',
            'apertura_programatica' => 'nullable|string|max:255',
            'nro_cite' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:1000',
            'justificacion' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.almacen_item_id' => 'nullable|exists:almacen_items,id',
            'items.*.imagen' => 'nullable|string|max:255',
            'items.*.descripcion' => 'required|string|max:500',
            'items.*.part_presup' => 'nullable|string|max:100',
            'items.*.unidad' => 'nullable|string|max:100',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $total = collect($data['items'])->sum(
                fn ($i) => (float) ($i['precio_unitario'] ?? 0) * (float) $i['cantidad']
            );

            $year = now()->year;
            $count = SolicitudSap::whereYear('created_at', $year)->withTrashed()->count() + 1;
            $nro = 'SAP-'.str_pad($count, 4, '0', STR_PAD_LEFT).'/'.$year;

            $user = $request->user()->load('unidad');
            $unidadNombre = $user->unidad?->nombre ?? $user->name;

            $solicitud = SolicitudSap::create([
                'user_id' => $user->id,
                'nro' => $nro,
                'fecha' => $data['fecha'],
                'unidad_solicitante' => $unidadNombre,
                'apertura_programatica' => $data['apertura_programatica'] ?? null,
                'nro_cite' => $data['nro_cite'] ?? null,
                'estado' => 'PENDIENTE',
                'observaciones' => $data['observaciones'] ?? null,
                'justificacion' => $data['justificacion'] ?? null,
                'total' => $total,
            ]);

            foreach ($data['items'] as $idx => $item) {
                $precio = (float) ($item['precio_unitario'] ?? 0);
                $cantidad = (float) $item['cantidad'];
                $solicitud->detalles()->create([
                    'almacen_item_id' => $item['almacen_item_id'] ?? null,
                    'imagen' => $item['imagen'] ?? null,
                    'item' => $idx + 1,
                    'part_presup' => $item['part_presup'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'unidad' => $item['unidad'] ?? null,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'total' => round($precio * $cantidad, 2),
                ]);
            }

            return response()->json($solicitud->load(['user:id,name', 'detalles']), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $solicitud = SolicitudSap::with('detalles')->findOrFail($id);

        $data = $request->validate([
            'fecha' => 'required|date',
            'unidad_solicitante' => 'nullable|string|max:255',
            'apertura_programatica' => 'nullable|string|max:255',
            'nro_cite' => 'nullable|string|max:100',
            'estado' => ['nullable', Rule::in(['PENDIENTE', 'APROBADO', 'RECHAZADO', 'ANULADO'])],
            'observaciones' => 'nullable|string|max:1000',
            'justificacion' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.almacen_item_id' => 'nullable|exists:almacen_items,id',
            'items.*.imagen' => 'nullable|string|max:255',
            'items.*.descripcion' => 'required|string|max:500',
            'items.*.part_presup' => 'nullable|string|max:100',
            'items.*.unidad' => 'nullable|string|max:100',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($solicitud, $data) {
            $total = collect($data['items'])->sum(
                fn ($i) => (float) ($i['precio_unitario'] ?? 0) * (float) $i['cantidad']
            );

            $solicitud->update([
                'fecha' => $data['fecha'],
                'unidad_solicitante' => $solicitud->unidad_solicitante,
                'apertura_programatica' => $data['apertura_programatica'] ?? null,
                'nro_cite' => $data['nro_cite'] ?? null,
                'estado' => $data['estado'] ?? $solicitud->estado,
                'observaciones' => $data['observaciones'] ?? null,
                'justificacion' => $data['justificacion'] ?? null,
                'total' => $total,
            ]);

            $solicitud->detalles()->delete();

            foreach ($data['items'] as $idx => $item) {
                $precio = (float) ($item['precio_unitario'] ?? 0);
                $cantidad = (float) $item['cantidad'];
                $solicitud->detalles()->create([
                    'almacen_item_id' => $item['almacen_item_id'] ?? null,
                    'imagen' => $item['imagen'] ?? null,
                    'item' => $idx + 1,
                    'part_presup' => $item['part_presup'] ?? null,
                    'descripcion' => $item['descripcion'],
                    'unidad' => $item['unidad'] ?? null,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'total' => round($precio * $cantidad, 2),
                ]);
            }

            return response()->json($solicitud->load(['user:id,name', 'detalles']));
        });
    }

    public function printPdf($id)
    {
        $solicitud = SolicitudSap::with(['user:id,name,firma,sello,mostrar_firma,mostrar_sello', 'detalles'])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.solicitud_sap_detalle', [
            'solicitud' => $solicitud,
        ])->setPaper('letter', 'portrait');

        $filename = 'solicitud_sap_'.str_replace(['/', '\\'], '-', $solicitud->nro).'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($filename);
    }

    public function destroy($id)
    {
        $solicitud = SolicitudSap::findOrFail($id);
        $solicitud->detalles()->delete();
        $solicitud->delete();

        return response()->json(['message' => 'Solicitud SAP eliminada correctamente']);
    }
}
