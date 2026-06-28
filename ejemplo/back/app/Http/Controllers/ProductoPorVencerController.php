<?php

namespace App\Http\Controllers;

use App\Models\CompraDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductoPorVencerController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->check() && method_exists(auth()->user(), 'can') && !auth()->user()->can('Módulo inventario')) {
            abort(403, 'No autorizado para ver productos por vencer');
        }

        $data = $request->validate([
            'valor' => 'nullable|integer|min:1|max:36500',
            'unidad' => ['nullable', Rule::in(['DIAS', 'MESES', 'ANIOS'])],
            'q' => 'nullable|string|max:255',
            'include_expired' => 'nullable|boolean',
            'rowsPerPage' => 'nullable|integer|min:1|max:200',
        ]);

        $valor = (int) ($data['valor'] ?? 30);
        $unidad = $data['unidad'] ?? 'DIAS';

        $from = now()->startOfDay();
        $to = match ($unidad) {
            'MESES' => (clone $from)->addMonths($valor),
            'ANIOS' => (clone $from)->addYears($valor),
            default => (clone $from)->addDays($valor),
        };

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
            ->whereDate('fecha_vencimiento', '<=', $to->toDateString());

        if (!$request->boolean('include_expired')) {
            $query->whereDate('fecha_vencimiento', '>=', $from->toDateString());
        }

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
            ->orderBy('fecha_vencimiento', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;
        $response['filters'] = [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'valor' => $valor,
            'unidad' => $unidad,
        ];

        return response()->json($response);
    }
}
