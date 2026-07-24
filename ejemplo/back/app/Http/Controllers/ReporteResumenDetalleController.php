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
        $meta = $this->periodoMeta($request);
        $resumen = $this->buildResumen($request, $meta);

        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(1000, (int) $request->input('per_page', 50)));
        $detalle = $this->buildDetalle($request, $meta, $page, $perPage);

        return response()->json([
            'resumen' => $resumen,
            'detalle' => $detalle,
            'meta' => $this->publicMeta($meta),
        ]);
    }

    public function detalleIndex(Request $request): JsonResponse
    {
        $meta = $this->periodoMeta($request);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(1000, (int) $request->input('per_page', 50)));
        $detalle = $this->buildDetalle($request, $meta, $page, $perPage);

        return response()->json(['detalle' => $detalle, 'meta' => $this->publicMeta($meta)]);
    }

    public function productos(Request $request, int $subpartidaId): JsonResponse
    {
        $meta = $this->periodoMeta($request);
        $detalle = $this->buildDetalle($request, $meta, null, null, $subpartidaId);

        return response()->json($detalle);
    }

    public function resumenPdf(Request $request)
    {
        $meta = $this->periodoMeta($request);
        $resumen = $this->buildResumen($request, $meta);
        $pdf = Pdf::loadView('reportes.resumen_almacen', [
            'rows' => $resumen['rows'],
            'total' => $resumen['total'],
            'meta' => $this->publicMeta($meta),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('DGCF-R1.05-Resumen-Almacen.pdf');
    }

    public function detallePdf(Request $request)
    {
        $meta = $this->periodoMeta($request);
        $detalle = $this->buildDetalle($request, $meta, null, null);
        $pdf = Pdf::loadView('reportes.detalle_almacen', [
            'rows' => $detalle['rows'],
            'meta' => $this->publicMeta($meta),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('DGCF-R1.06-Detalle-Almacen.pdf');
    }

    public function resumenExcel(Request $request)
    {
        $meta = $this->periodoMeta($request);
        $resumen = $this->buildResumen($request, $meta);

        return Excel::download(
            new ResumenAlmacenExport($resumen['rows'], $resumen['total'], $this->publicMeta($meta)),
            'DGCF-R1.05-Resumen-Almacen.xlsx'
        );
    }

    public function detalleExcel(Request $request)
    {
        $meta = $this->periodoMeta($request);
        $detalle = $this->buildDetalle($request, $meta, null, null);

        return Excel::download(
            new DetalleAlmacenExport($detalle['rows'], $this->publicMeta($meta)),
            'DGCF-R1.06-Detalle-Almacen.xlsx'
        );
    }

    public function movimientos(Request $request, int $itemId): JsonResponse
    {
        $meta = $this->periodoMeta($request);
        $desde = $meta['desde_dt'];
        $hasta = $meta['hasta_dt'];

        $despachos = DB::table('despacho_detalle_reales as ddr')
            ->join('despachos as d', 'ddr.despacho_id', '=', 'd.id')
            ->leftJoin('unidades as u', 'd.unidad_id', '=', 'u.id')
            ->where('ddr.almacen_item_id', $itemId)
            ->whereNull('ddr.deleted_at')
            ->whereNull('d.deleted_at')
            ->whereBetween('d.fecha_entrega', [$desde, $hasta])
            ->select(
                DB::raw("'Despacho' as tipo"),
                'd.fecha_entrega as fecha',
                'd.nro as documento',
                DB::raw('COALESCE(u.nombre, d.solicitante) as destino'),
                'ddr.cantidad',
                'ddr.precio_unitario',
                'ddr.total',
                'ddr.lote'
            )
            ->get();

        $directas = DB::table('compra_detalles as cd')
            ->join('compras as c', 'cd.compra_id', '=', 'c.id')
            ->leftJoin('unidades as u', 'c.unidad_id', '=', 'u.id')
            ->where('cd.producto_id', $itemId)
            ->where('c.tipo_registro', 'SALIDA')
            ->where('c.estado', 'ACTIVO')
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.fecha_hora', [$desde, $hasta])
            ->select(
                DB::raw("'Salida directa' as tipo"),
                'c.fecha_hora as fecha',
                'c.numero as documento',
                DB::raw('COALESCE(u.nombre, c.nombre) as destino'),
                'cd.cantidad',
                'cd.precio as precio_unitario',
                'cd.total',
                'cd.lote'
            )
            ->get();

        $rows = $despachos->concat($directas)
            ->sortBy('fecha')
            ->values()
            ->map(function ($r, $i) {
                return [
                    'nro' => $i + 1,
                    'tipo' => $r->tipo,
                    'fecha' => $r->fecha,
                    'documento' => $r->documento,
                    'destino' => $r->destino,
                    'cantidad' => (float) $r->cantidad,
                    'precio_unitario' => (float) $r->precio_unitario,
                    'total' => (float) $r->total,
                    'lote' => $r->lote,
                ];
            })
            ->all();

        return response()->json(['rows' => $rows]);
    }

    // ─── core ────────────────────────────────────────────────────────────────

    private function periodoMeta(Request $request): array
    {
        $year = now()->year;
        $dRaw = $request->input('desde', "{$year}-01-01");
        $hRaw = $request->input('hasta', "{$year}-12-31");

        $periodoDesde = \Carbon\Carbon::parse($dRaw)->translatedFormat('d \d\e F \d\e Y');
        $periodoHasta = \Carbon\Carbon::parse($hRaw)->translatedFormat('d \d\e F \d\e Y');

        return [
            'desde' => $dRaw,
            'hasta' => $hRaw,
            'desde_dt' => $dRaw.' 00:00:00',
            'hasta_dt' => $hRaw.' 23:59:59',
            'periodo' => 'DEL '.strtoupper($periodoDesde).' AL '.strtoupper($periodoHasta),
        ];
    }

    private function publicMeta(array $meta): array
    {
        return ['desde' => $meta['desde'], 'hasta' => $meta['hasta'], 'periodo' => $meta['periodo']];
    }

    /**
     * Resumen agrupado por subpartida. Se calcula íntegramente con SQL agregado
     * (sin materializar cada ítem en PHP) porque el universo de subpartidas es
     * pequeño y no necesita paginación.
     */
    private function buildResumen(Request $request, array $meta): array
    {
        $desde = $meta['desde_dt'];
        $hasta = $meta['hasta_dt'];

        $excludeIds = array_values(array_filter((array) $request->input('exclude_subpartida_ids', [])));

        $subpartidas = DB::table('almacen_items as ai')
            ->join('subpartidas as sp', 'ai.subpartida_id', '=', 'sp.id')
            ->join('partidas as p', 'sp.partida_id', '=', 'p.id')
            ->whereNull('ai.deleted_at')
            ->when($excludeIds, fn ($q) => $q->whereNotIn('sp.id', $excludeIds))
            ->select('sp.id as subpartida_id', 'sp.codigo as subpartida_codigo', 'sp.nombre as subpartida_nombre', 'p.codigo as partida_codigo')
            ->distinct()
            ->orderBy('p.codigo')
            ->orderBy('sp.codigo')
            ->get();

        if ($subpartidas->isEmpty()) {
            return ['rows' => [], 'total' => null];
        }

        $inicialId = $this->inventarioInicialCompraId();
        $iniQty = $this->inicialBySubpartida($inicialId, 'cd.cantidad');
        $iniVal = $this->inicialBySubpartida($inicialId, 'cd.total');

        $entAntesQty = $this->compraAggregateBySubpartida('ENTRADA', null, $desde, 'cd.cantidad', $inicialId);
        $entAntesVal = $this->compraAggregateBySubpartida('ENTRADA', null, $desde, 'cd.total', $inicialId);
        $salAntesQty = $this->salidasBySubpartida(null, $desde, 'cantidad', 'cd.cantidad');
        $salAntesVal = $this->salidasBySubpartida(null, $desde, 'total', 'cd.total');

        $entQty = $this->compraAggregateBySubpartida('ENTRADA', $desde, $hasta, 'cd.cantidad', $inicialId);
        $entVal = $this->compraAggregateBySubpartida('ENTRADA', $desde, $hasta, 'cd.total', $inicialId);
        $salQty = $this->salidasBySubpartida($desde, $hasta, 'cantidad', 'cd.cantidad');
        $salVal = $this->salidasBySubpartida($desde, $hasta, 'total', 'cd.total');

        $nro = 0;
        $rows = [];

        foreach ($subpartidas as $sp) {
            $sid = $sp->subpartida_id;

            $csi = ($iniQty[$sid] ?? 0) + ($entAntesQty[$sid] ?? 0) - ($salAntesQty[$sid] ?? 0);
            $vsi = ($iniVal[$sid] ?? 0) + ($entAntesVal[$sid] ?? 0) - ($salAntesVal[$sid] ?? 0);
            $ce = $entQty[$sid] ?? 0;
            $ve = $entVal[$sid] ?? 0;
            $cs = $salQty[$sid] ?? 0;
            $vs = $salVal[$sid] ?? 0;

            $rows[] = [
                'nro' => ++$nro,
                'subpartida_id' => $sid,
                'detalle' => $sp->subpartida_nombre,
                'subpartida' => $sp->subpartida_codigo,
                'cant_ini' => $csi,
                'saldo_ini' => round($vsi, 2),
                'cant_final' => $csi + $ce - $cs,
                'saldo_final' => round($vsi + $ve - $vs, 2),
            ];
        }

        $total = $rows ? [
            'cant_ini' => array_sum(array_column($rows, 'cant_ini')),
            'saldo_ini' => round(array_sum(array_column($rows, 'saldo_ini')), 2),
            'cant_final' => array_sum(array_column($rows, 'cant_final')),
            'saldo_final' => round(array_sum(array_column($rows, 'saldo_final')), 2),
        ] : null;

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Detalle por ítem. Soporta paginación ($page/$perPage null = todo, usado
     * por los reportes) y filtro opcional por subpartida (drill-down del resumen).
     * Los reportes excluyen únicamente lo que el usuario desmarque explícitamente
     * (exclude_ids), de forma que por defecto siempre exportan el universo completo.
     */
    private function buildDetalle(Request $request, array $meta, ?int $page, ?int $perPage, ?int $subpartidaId = null): array
    {
        $desde = $meta['desde_dt'];
        $hasta = $meta['hasta_dt'];

        $excludeIds = array_values(array_filter((array) $request->input('exclude_ids', [])));
        $search = trim((string) $request->input('q', ''));

        $query = DB::table('almacen_items as ai')
            ->join('subpartidas as sp', 'ai.subpartida_id', '=', 'sp.id')
            ->join('partidas as p', 'sp.partida_id', '=', 'p.id')
            ->whereNull('ai.deleted_at')
            ->when($excludeIds, fn ($q) => $q->whereNotIn('ai.id', $excludeIds))
            ->when($subpartidaId, fn ($q) => $q->where('sp.id', $subpartidaId))
            ->when($search !== '', fn ($q) => $q->where('ai.nombre', 'like', "%{$search}%"));

        $total = (clone $query)->count();

        $query->orderBy('p.codigo')->orderBy('sp.codigo')->orderBy('ai.nombre')
            ->select(
                'ai.id', 'ai.nombre', 'ai.unidad_medida', 'ai.precio_unitario',
                'sp.id as subpartida_id', 'sp.codigo as subpartida_codigo', 'sp.nombre as subpartida_nombre',
                'p.id as partida_id', 'p.codigo as partida_codigo', 'p.nombre as partida_nombre'
            );

        if ($page !== null && $perPage !== null) {
            $query->skip(($page - 1) * $perPage)->take($perPage);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return ['rows' => [], 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        }

        $ids = $items->pluck('id')->toArray();

        $inicialId = $this->inventarioInicialCompraId();
        $iniQty = $this->inicialByItem($ids, $inicialId, 'cd.cantidad');
        $iniVal = $this->inicialByItem($ids, $inicialId, 'cd.total');

        $entAntesQty = $this->entradasQty($ids, null, $desde, $inicialId);
        $entAntesVal = $this->entradasVal($ids, null, $desde, $inicialId);
        $salAntesQty = $this->salidasQty($ids, null, $desde);
        $salAntesVal = $this->salidasVal($ids, null, $desde);

        $entQty = $this->entradasQty($ids, $desde, $hasta, $inicialId);
        $entVal = $this->entradasVal($ids, $desde, $hasta, $inicialId);
        $salQty = $this->salidasQty($ids, $desde, $hasta);
        $salVal = $this->salidasVal($ids, $desde, $hasta);

        $nro = $page !== null ? ($page - 1) * $perPage : 0;
        $rows = [];

        foreach ($items as $item) {
            $id = $item->id;

            $csi = ($iniQty[$id] ?? 0) + ($entAntesQty[$id] ?? 0) - ($salAntesQty[$id] ?? 0);
            $vsi = ($iniVal[$id] ?? 0) + ($entAntesVal[$id] ?? 0) - ($salAntesVal[$id] ?? 0);
            $ce = $entQty[$id] ?? 0;
            $ve = $entVal[$id] ?? 0;
            $cs = $salQty[$id] ?? 0;
            $vs = $salVal[$id] ?? 0;

            $rows[] = [
                'nro' => ++$nro,
                'id' => $item->id,
                'descripcion' => $item->nombre,
                'unidad' => $item->unidad_medida ?? '',
                'precio_unitario' => (float) $item->precio_unitario,
                'subpartida_id' => $item->subpartida_id,
                'subpartida_codigo' => $item->subpartida_codigo,
                'subpartida_nombre' => $item->subpartida_nombre,
                'subpartida' => $item->subpartida_codigo.' '.$item->subpartida_nombre,
                'partida_id' => $item->partida_id,
                'partida_codigo' => $item->partida_codigo,
                'partida_nombre' => $item->partida_nombre,
                'cant_saldo_ini' => $csi,
                'cant_entradas' => $ce,
                'cant_salidas' => $cs,
                'cant_saldo_final' => $csi + $ce - $cs,
                'val_saldo_ini' => round($vsi, 2),
                'val_entradas' => round($ve, 2),
                'val_salidas' => round($vs, 2),
                'val_saldo_final' => round($vsi + $ve - $vs, 2),
            ];
        }

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    // ─── helpers de queries: inventario inicial ──────────────────────────────

    /**
     * La primera compra ENTRADA registrada es la carga de inventario inicial:
     * siempre forma parte del saldo inicial sin importar su fecha, y se excluye
     * de las entradas para no contarla dos veces.
     */
    private function inventarioInicialCompraId(): ?int
    {
        return DB::table('compras')
            ->where('tipo_registro', 'ENTRADA')
            ->where('estado', 'ACTIVO')
            ->whereNull('deleted_at')
            ->min('id');
    }

    private function inicialByItem(array $ids, ?int $compraId, string $col): array
    {
        if (! $compraId) {
            return [];
        }

        return DB::table('compra_detalles as cd')
            ->where('cd.compra_id', $compraId)
            ->whereIn('cd.producto_id', $ids)
            ->whereNull('cd.deleted_at')
            ->groupBy('cd.producto_id')
            ->select('cd.producto_id', DB::raw("SUM({$col}) as total"))
            ->pluck('total', 'producto_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function inicialBySubpartida(?int $compraId, string $col): array
    {
        if (! $compraId) {
            return [];
        }

        return DB::table('compra_detalles as cd')
            ->join('almacen_items as ai', 'cd.producto_id', '=', 'ai.id')
            ->where('cd.compra_id', $compraId)
            ->whereNull('cd.deleted_at')
            ->whereNull('ai.deleted_at')
            ->groupBy('ai.subpartida_id')
            ->select('ai.subpartida_id', DB::raw("SUM({$col}) as total"))
            ->pluck('total', 'subpartida_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    // ─── helpers de queries: por ítem ─────────────────────────────────────────

    private function entradasQty(array $ids, ?string $desde, ?string $hasta, ?int $excludeCompraId = null): array
    {
        return $this->compraAggregate($ids, 'ENTRADA', $desde, $hasta, 'cd.cantidad', $excludeCompraId);
    }

    private function entradasVal(array $ids, ?string $desde, ?string $hasta, ?int $excludeCompraId = null): array
    {
        return $this->compraAggregate($ids, 'ENTRADA', $desde, $hasta, 'cd.total', $excludeCompraId);
    }

    private function salidasQty(array $ids, ?string $desde, ?string $hasta): array
    {
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

    private function compraAggregate(array $ids, string $tipo, ?string $desde, ?string $hasta, string $col, ?int $excludeCompraId = null): array
    {
        $q = DB::table('compra_detalles as cd')
            ->join('compras as c', 'cd.compra_id', '=', 'c.id')
            ->whereIn('cd.producto_id', $ids)
            ->where('c.tipo_registro', $tipo)
            ->where('c.estado', 'ACTIVO')
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->when($excludeCompraId, fn ($q) => $q->where('cd.compra_id', '!=', $excludeCompraId));

        if ($desde && $hasta) {
            $q->whereBetween('c.fecha_hora', [$desde, $hasta]);
        } elseif ($hasta) {
            $q->where('c.fecha_hora', '<', $hasta);
        }

        return $q->groupBy('cd.producto_id')
            ->select('cd.producto_id', DB::raw("SUM({$col}) as total"))
            ->pluck('total', 'producto_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function despachoAggregate(array $ids, ?string $desde, ?string $hasta, string $col): array
    {
        $q = DB::table('despacho_detalle_reales as ddr')
            ->join('despachos as d', 'ddr.despacho_id', '=', 'd.id')
            ->whereIn('ddr.almacen_item_id', $ids)
            ->whereNull('ddr.deleted_at')
            ->whereNull('d.deleted_at');

        if ($desde && $hasta) {
            $q->whereBetween('d.fecha_entrega', [$desde, $hasta]);
        } elseif ($hasta) {
            $q->where('d.fecha_entrega', '<', $hasta);
        }

        return $q->groupBy('ddr.almacen_item_id')
            ->select('ddr.almacen_item_id', DB::raw("SUM(ddr.{$col}) as total"))
            ->pluck('total', 'almacen_item_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    // ─── helpers de queries: por subpartida (para el resumen) ────────────────

    private function salidasBySubpartida(?string $desde, ?string $hasta, string $despachoCol, string $compraCol): array
    {
        $despacho = $this->despachoAggregateBySubpartida($desde, $hasta, $despachoCol);
        $directas = $this->compraAggregateBySubpartida('SALIDA', $desde, $hasta, $compraCol);

        return $this->mergeSum($despacho, $directas);
    }

    private function compraAggregateBySubpartida(string $tipo, ?string $desde, ?string $hasta, string $col, ?int $excludeCompraId = null): array
    {
        $q = DB::table('compra_detalles as cd')
            ->join('compras as c', 'cd.compra_id', '=', 'c.id')
            ->join('almacen_items as ai', 'cd.producto_id', '=', 'ai.id')
            ->where('c.tipo_registro', $tipo)
            ->where('c.estado', 'ACTIVO')
            ->whereNull('cd.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNull('ai.deleted_at')
            ->when($excludeCompraId, fn ($q) => $q->where('cd.compra_id', '!=', $excludeCompraId));

        if ($desde && $hasta) {
            $q->whereBetween('c.fecha_hora', [$desde, $hasta]);
        } elseif ($hasta) {
            $q->where('c.fecha_hora', '<', $hasta);
        }

        return $q->groupBy('ai.subpartida_id')
            ->select('ai.subpartida_id', DB::raw("SUM({$col}) as total"))
            ->pluck('total', 'subpartida_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function despachoAggregateBySubpartida(?string $desde, ?string $hasta, string $col): array
    {
        $q = DB::table('despacho_detalle_reales as ddr')
            ->join('despachos as d', 'ddr.despacho_id', '=', 'd.id')
            ->join('almacen_items as ai', 'ddr.almacen_item_id', '=', 'ai.id')
            ->whereNull('ddr.deleted_at')
            ->whereNull('d.deleted_at')
            ->whereNull('ai.deleted_at');

        if ($desde && $hasta) {
            $q->whereBetween('d.fecha_entrega', [$desde, $hasta]);
        } elseif ($hasta) {
            $q->where('d.fecha_entrega', '<', $hasta);
        }

        return $q->groupBy('ai.subpartida_id')
            ->select('ai.subpartida_id', DB::raw("SUM(ddr.{$col}) as total"))
            ->pluck('total', 'subpartida_id')
            ->map(fn ($v) => (float) $v)
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
