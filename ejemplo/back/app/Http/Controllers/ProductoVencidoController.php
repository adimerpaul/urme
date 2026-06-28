<?php

namespace App\Http\Controllers;

use App\Models\CompraDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductoVencidoController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->check() && method_exists(auth()->user(), 'can') && !auth()->user()->can('Módulo inventario')) {
            abort(403, 'No autorizado para ver productos vencidos');
        }

        $data = $request->validate([
            'q' => 'nullable|string|max:255',
            'hasta' => 'nullable|date',
            'rowsPerPage' => 'nullable|integer|min:1|max:200',
        ]);

        $hasta = $request->filled('hasta')
            ? now()->parse($data['hasta'])->endOfDay()
            : now()->endOfDay();

        $query = CompraDetalle::query()
            ->with([
                'producto:id,nombre,unidad_medida,imagen',
                'compra:id,fecha_hora,user_id,proveedor_id,nro_factura,tipo_registro,estado',
                'compra.user:id,name',
                'compra.proveedor:id,nombre',
            ])
            ->whereNotNull('fecha_vencimiento')
            ->whereRaw("UPPER(COALESCE(compra_detalles.estado, '')) = 'ACTIVO'")
            ->whereHas('compra', function ($query) {
                $query->where('estado', 'ACTIVO')
                    ->where('tipo_registro', 'ENTRADA');
            })
            ->whereRaw('(COALESCE(compra_detalles.cantidad, 0) - COALESCE(compra_detalles.cantidad_venta, 0)) > 0')
            ->whereDate('fecha_vencimiento', '<', $hasta->toDateString());

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('compra_detalles.nombre', 'like', "%{$q}%")
                    ->orWhere('compra_detalles.lote', 'like', "%{$q}%")
                    ->orWhere('compra_detalles.nro_factura', 'like', "%{$q}%")
                    ->orWhereHas('compra.proveedor', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
                    ->orWhereHas('compra.user', fn ($query) => $query->where('name', 'like', "%{$q}%"));
            });
        }

        $rowsPerPage = (int) $request->input('rowsPerPage', 15);
        $rowsPerPage = $rowsPerPage > 0 ? $rowsPerPage : 15;

        $summaryBase = (clone $query)->toBase();
        $summary = [
            'existencia_total' => (int) ($summaryBase->sum(DB::raw('(COALESCE(compra_detalles.cantidad, 0) - COALESCE(compra_detalles.cantidad_venta, 0))')) ?? 0),
        ];

        $paginated = $query
            ->orderBy('fecha_vencimiento', 'desc')
            ->orderBy('id', 'asc')
            ->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;
        $response['filters'] = [
            'hasta' => $hasta->toDateString(),
        ];

        return response()->json($response);
    }
}

