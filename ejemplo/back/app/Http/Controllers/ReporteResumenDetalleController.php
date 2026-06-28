<?php

namespace App\Http\Controllers;

use App\Exports\DetalleAlmacenExport;
use App\Exports\ResumenAlmacenExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReporteResumenDetalleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        ['resumen' => $resumen, 'detalle' => $detalle, 'meta' => $meta] = $this->build($request);
        return response()->json(compact('resumen', 'detalle', 'meta'));
    }

    public function resumenPdf(Request $request)
    {
        ['resumen' => $resumen, 'meta' => $meta] = $this->build($request);
        $pdf = Pdf::loadView('reportes.resumen_almacen', [
            'rows'  => $resumen['rows'],
            'total' => $resumen['total'],
            'meta'  => $meta,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('DGCF-R1.05-Resumen-Almacen.pdf');
    }

    public function detallePdf(Request $request)
    {
        ['detalle' => $detalle, 'meta' => $meta] = $this->build($request);
        $pdf = Pdf::loadView('reportes.detalle_almacen', [
            'rows' => $detalle['rows'],
            'meta' => $meta,
        ])->setPaper('a4', 'landscape');
        return $pdf->download('DGCF-R1.06-Detalle-Almacen.pdf');
    }

    public function resumenExcel(Request $request)
    {
        ['resumen' => $resumen, 'meta' => $meta] = $this->build($request);
        return Excel::download(
            new ResumenAlmacenExport($resumen['rows'], $resumen['total'], $meta),
            'DGCF-R1.05-Resumen-Almacen.xlsx'
        );
    }

    public function detalleExcel(Request $request)
    {
        ['detalle' => $detalle, 'meta' => $meta] = $this->build($request);
        return Excel::download(
            new DetalleAlmacenExport($detalle['rows'], $meta),
            'DGCF-R1.06-Detalle-Almacen.xlsx'
        );
    }

    // ─── core ────────────────────────────────────────────────────────────────

    private function build(Request $request): array
    {
        $year  = now()->year;
        $dRaw  = $request->input('desde', "{$year}-01-01");
        $hRaw  = $request->input('hasta', "{$year}-12-31");
        $desde = $dRaw . ' 00:00:00';
        $hasta = $hRaw . ' 23:59:59';

        $periodoDesde = \Carbon\Carbon::parse($dRaw)->translatedFormat('d \d\e F \d\e Y');
        $periodoHasta = \Carbon\Carbon::parse($hRaw)->translatedFormat('d \d\e F \d\e Y');

        $meta = [
            'desde'   => $dRaw,
            'hasta'   => $hRaw,
            'periodo' => 'DEL ' . strtoupper($periodoDesde) . ' AL ' . strtoupper($periodoHasta),
        ];

        // ── items con jerarquía ───────────────────────────────────────────────
        $items = DB::table('almacen_items as ai')
            ->join('subpartidas as sp', 'ai.subpartida_id', '=', 'sp.id')
            ->join('partidas as p', 'sp.partida_id', '=', 'p.id')
            ->whereNull('ai.deleted_at')
            ->orderBy('p.codigo')
            ->orderBy('sp.codigo')
            ->orderBy('ai.nombre')
            ->select(
                'ai.id', 'ai.nombre', 'ai.unidad_medida', 'ai.precio_unitario',
                'sp.id as subpartida_id', 'sp.codigo as subpartida_codigo', 'sp.nombre as subpartida_nombre',
                'p.id as partida_id', 'p.codigo as partida_codigo', 'p.nombre as partida_nombre'
            )
            ->get();

        if ($items->isEmpty()) {
            return ['resumen' => ['rows' => [], 'total' => null], 'detalle' => ['rows' => []], 'meta' => $meta];
        }

        $ids = $items->pluck('id')->toArray();

        // ── entradas antes del período ────────────────────────────────────────
        $entAntesQty = $this->entradasQty($ids, null, $desde);
        $entAntesVal = $this->entradasVal($ids, null, $desde);

        // ── salidas antes del período ─────────────────────────────────────────
        $salAntesQty = $this->salidasQty($ids, null, $desde);
        $salAntesVal = $this->salidasVal($ids, null, $desde);

        // ── entradas del período ──────────────────────────────────────────────
        $entQty = $this->entradasQty($ids, $desde, $hasta);
        $entVal = $this->entradasVal($ids, $desde, $hasta);

        // ── salidas del período ───────────────────────────────────────────────
        $salQty = $this->salidasQty($ids, $desde, $hasta);
        $salVal = $this->salidasVal($ids, $desde, $hasta);

        // ── construir detalle ─────────────────────────────────────────────────
        $nro = 0;
        $detalleRows = [];

        foreach ($items as $item) {
            $id = $item->id;

            $csi  = ($entAntesQty[$id] ?? 0) - ($salAntesQty[$id] ?? 0);
            $vsi  = ($entAntesVal[$id] ?? 0) - ($salAntesVal[$id] ?? 0);
            $ce   = $entQty[$id] ?? 0;
            $ve   = $entVal[$id] ?? 0;
            $cs   = $salQty[$id] ?? 0;
            $vs   = $salVal[$id] ?? 0;
            $csf  = $csi + $ce - $cs;
            $vsf  = $vsi + $ve - $vs;

            $detalleRows[] = [
                'nro'              => ++$nro,
                'descripcion'      => $item->nombre,
                'unidad'           => $item->unidad_medida ?? '',
                'precio_unitario'  => (float) $item->precio_unitario,
                'subpartida_id'    => $item->subpartida_id,
                'subpartida'       => $item->subpartida_codigo . ' ' . $item->subpartida_nombre,
                'partida_id'       => $item->partida_id,
                'partida_codigo'   => $item->partida_codigo,
                'partida_nombre'   => $item->partida_nombre,
                'cant_saldo_ini'   => $csi,
                'cant_entradas'    => $ce,
                'cant_salidas'     => $cs,
                'cant_saldo_final' => $csf,
                'val_saldo_ini'    => round($vsi, 2),
                'val_entradas'     => round($ve, 2),
                'val_salidas'      => round($vs, 2),
                'val_saldo_final'  => round($vsf, 2),
            ];
        }

        // ── construir resumen agrupado por partida ────────────────────────────
        $porPartida = [];
        foreach ($detalleRows as $r) {
            $pid = $r['partida_id'];
            if (!isset($porPartida[$pid])) {
                $porPartida[$pid] = [
                    'partida_codigo' => $r['partida_codigo'],
                    'partida_nombre' => $r['partida_nombre'],
                    'cant_ini'    => 0, 'saldo_ini'   => 0.0,
                    'cant_final'  => 0, 'saldo_final' => 0.0,
                ];
            }
            $porPartida[$pid]['cant_ini']    += $r['cant_saldo_ini'];
            $porPartida[$pid]['saldo_ini']   += $r['val_saldo_ini'];
            $porPartida[$pid]['cant_final']  += $r['cant_saldo_final'];
            $porPartida[$pid]['saldo_final'] += $r['val_saldo_final'];
        }

        $rNro = 0;
        $resumenRows = [];
        foreach ($porPartida as $row) {
            $resumenRows[] = [
                'nro'         => ++$rNro,
                'detalle'     => $row['partida_nombre'],
                'partida'     => $row['partida_codigo'],
                'cant_ini'    => $row['cant_ini'],
                'saldo_ini'   => round($row['saldo_ini'], 2),
                'cant_final'  => $row['cant_final'],
                'saldo_final' => round($row['saldo_final'], 2),
            ];
        }

        $total = $resumenRows ? [
            'cant_ini'    => array_sum(array_column($resumenRows, 'cant_ini')),
            'saldo_ini'   => round(array_sum(array_column($resumenRows, 'saldo_ini')), 2),
            'cant_final'  => array_sum(array_column($resumenRows, 'cant_final')),
            'saldo_final' => round(array_sum(array_column($resumenRows, 'saldo_final')), 2),
        ] : null;

        return [
            'resumen' => ['rows' => $resumenRows, 'total' => $total],
            'detalle' => ['rows' => $detalleRows],
            'meta'    => $meta,
        ];
    }

    // ─── helpers de queries ───────────────────────────────────────────────────

    private function entradasQty(array $ids, ?string $desde, ?string $hasta): array
    {
        return $this->compraAggregate($ids, 'ENTRADA', $desde, $hasta, 'cd.cantidad');
    }

    private function entradasVal(array $ids, ?string $desde, ?string $hasta): array
    {
        return $this->compraAggregate($ids, 'ENTRADA', $desde, $hasta, 'cd.total');
    }

    private function salidasQty(array $ids, ?string $desde, ?string $hasta): array
    {
        // despacho_detalle_reales + compras tipo SALIDA
        $despacho = $this->despachoAggregate($ids, $desde, $hasta, 'cantidad');
        $directas = $this->compraAggregate($ids, 'SALIDA', $desde, $hasta, 'cd.cantidad');
        return $this->mergeSum($despacho, $directas);
    }

    private function salidasVal(array $ids, ?string $desde, ?string $hasta): array
    {
        $despacho = $this->despachoAggregate($ids, $desde, $hasta, 'total');
        $directas = $this->compraAggregate($ids, 'SALIDA', $desde, $hasta, 'cd.total');
        return $this->mergeSum($despacho, $directas);
    }

    private function compraAggregate(array $ids, string $tipo, ?string $desde, ?string $hasta, string $col): array
    {
        $q = DB::table('compra_detalles as cd')
            ->join('compras as c', 'cd.compra_id', '=', 'c.id')
            ->whereIn('cd.producto_id', $ids)
            ->where('c.tipo_registro', $tipo)
            ->where('c.estado', 'ACTIVO')
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at');

        if ($desde && $hasta) $q->whereBetween('c.fecha_hora', [$desde, $hasta]);
        elseif ($hasta)       $q->where('c.fecha_hora', '<', $hasta);

        return $q->groupBy('cd.producto_id')
            ->select('cd.producto_id', DB::raw("SUM({$col}) as total"))
            ->pluck('total', 'producto_id')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    private function despachoAggregate(array $ids, ?string $desde, ?string $hasta, string $col): array
    {
        $q = DB::table('despacho_detalle_reales as ddr')
            ->join('despachos as d', 'ddr.despacho_id', '=', 'd.id')
            ->whereIn('ddr.almacen_item_id', $ids)
            ->whereNull('ddr.deleted_at')
            ->whereNull('d.deleted_at');

        if ($desde && $hasta) $q->whereBetween('d.fecha_entrega', [$desde, $hasta]);
        elseif ($hasta)       $q->where('d.fecha_entrega', '<', $hasta);

        return $q->groupBy('ddr.almacen_item_id')
            ->select('ddr.almacen_item_id', DB::raw("SUM(ddr.{$col}) as total"))
            ->pluck('total', 'almacen_item_id')
            ->map(fn($v) => (float) $v)
            ->toArray();
    }

    private function mergeSum(array $a, array $b): array
    {
        $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
        $result = [];
        foreach ($keys as $k) {
            $result[$k] = ($a[$k] ?? 0) + ($b[$k] ?? 0);
        }
        return $result;
    }
}
