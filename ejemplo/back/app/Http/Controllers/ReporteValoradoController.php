<?php

namespace App\Http\Controllers;

use App\Exports\ReporteValoradoExport;
use App\Models\AlmacenItem;
use App\Models\CompraDetalle;
use App\Models\DespachoDetalleReal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteValoradoController extends Controller
{
    public function index(Request $request)
    {
        $productoId = $request->input('producto_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($productoId) {
            $productos = AlmacenItem::where('id', $productoId)->get();
        } else {
            $productos = AlmacenItem::orderBy('nombre')->get();
        }

        $resultado = [];

        foreach ($productos as $producto) {
            $card = $this->buildPepsCard($producto, $dateFrom, $dateTo);
            if (count($card['rows']) > 0) {
                $resultado[] = $card;
            }
        }

        return response()->json($resultado);
    }

    public function pdf(Request $request)
    {
        $productoId = $request->input('producto_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($productoId) {
            $productos = AlmacenItem::where('id', $productoId)->get();
        } else {
            $productos = AlmacenItem::orderBy('nombre')->get();
        }

        $cards = [];
        foreach ($productos as $producto) {
            $card = $this->buildPepsCard($producto, $dateFrom, $dateTo);
            if (count($card['rows']) > 0) {
                $cards[] = $card;
            }
        }

        $pdf = Pdf::loadView('reportes.reporte_valorado', [
            'cards' => $cards,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ])->setPaper('letter', 'landscape');

        $filename = 'reporte_valorado_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($filename);
    }

    public function excel(Request $request)
    {
        $productoId = $request->input('producto_id');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');

        $productos = $productoId
            ? AlmacenItem::where('id', $productoId)->get()
            : AlmacenItem::orderBy('nombre')->get();

        $cards = [];
        foreach ($productos as $producto) {
            $card = $this->buildPepsCard($producto, $dateFrom, $dateTo);
            if (count($card['rows']) > 0) {
                $cards[] = $card;
            }
        }

        $filename = 'reporte_valorado_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new ReporteValoradoExport($cards, $dateFrom, $dateTo),
            $filename
        );
    }

    private function buildPepsCard(AlmacenItem $producto, ?string $dateFrom, ?string $dateTo): array
    {
        $entradas = CompraDetalle::with('compra')
            ->where('producto_id', $producto->id)
            ->where('estado', 'ACTIVO')
            ->whereHas('compra', fn ($q) => $q->where('tipo_registro', 'ENTRADA')->where('estado', 'ACTIVO'))
            ->when($dateFrom, fn ($q) => $q->whereHas('compra', fn ($cq) => $cq->whereDate('fecha_hora', '>=', $dateFrom)))
            ->when($dateTo, fn ($q) => $q->whereHas('compra', fn ($cq) => $cq->whereDate('fecha_hora', '<=', $dateTo)))
            ->get()
            ->map(fn ($det) => [
                'tipo' => 'ENTRADA',
                'fecha' => $det->compra->fecha_hora,
                'concepto' => $det->compra->motivo_registro ?? 'COMPRA',
                'cantidad' => (int) $det->cantidad,
                'precio_unitario' => (float) $det->precio,
                'total' => (float) $det->total,
            ]);

        $salidas = DespachoDetalleReal::with('despacho')
            ->where('almacen_item_id', $producto->id)
            ->whereHas('despacho', fn ($q) => $q->where('estado', '!=', 'ANULADO'))
            ->when($dateFrom, fn ($q) => $q->whereHas('despacho', fn ($dq) => $dq->whereDate('fecha_entrega', '>=', $dateFrom)))
            ->when($dateTo, fn ($q) => $q->whereHas('despacho', fn ($dq) => $dq->whereDate('fecha_entrega', '<=', $dateTo)))
            ->get()
            ->map(fn ($real) => [
                'tipo' => 'SALIDA',
                'fecha' => $real->despacho->fecha_entrega,
                'concepto' => 'DESPACHO #'.$real->despacho_id,
                'cantidad' => (int) $real->cantidad,
                'precio_unitario' => (float) $real->precio_unitario,
                'total' => (float) $real->total,
            ]);

        $movimientos = $entradas->concat($salidas)
            ->sortBy('fecha')
            ->values();

        $saldoCantidad = 0;
        $saldoTotal = 0.0;

        $rows = $movimientos->map(function ($mov) use (&$saldoCantidad, &$saldoTotal) {
            if ($mov['tipo'] === 'ENTRADA') {
                $saldoCantidad += $mov['cantidad'];
                $saldoTotal += $mov['total'];
            } else {
                $saldoCantidad -= $mov['cantidad'];
                $saldoTotal -= $mov['total'];
            }
            $mov['saldo_cantidad'] = $saldoCantidad;
            $mov['saldo_total'] = round($saldoTotal, 2);
            $mov['saldo_precio_unitario'] = $saldoCantidad > 0 ? round($saldoTotal / $saldoCantidad, 4) : 0;

            return $mov;
        })->values();

        $summary = [
            'total_entradas_cantidad' => $entradas->sum('cantidad'),
            'total_entradas_valor' => round($entradas->sum('total'), 2),
            'total_salidas_cantidad' => $salidas->sum('cantidad'),
            'total_salidas_valor' => round($salidas->sum('total'), 2),
            'costo_ventas' => round($salidas->sum('total'), 2),
            'saldo_final_cantidad' => $saldoCantidad,
            'saldo_final_valor' => round($saldoTotal, 2),
        ];

        return [
            'producto_id' => $producto->id,
            'producto' => $producto->nombre,
            'unidad' => $producto->unidad_medida ?? 'PZA',
            'rows' => $rows->toArray(),
            'summary' => $summary,
        ];
    }
}
