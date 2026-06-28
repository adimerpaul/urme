<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ReporteUnidadController extends Controller
{
    public function unidades()
    {
        $unidades = DB::table('despachos')
            ->whereNull('deleted_at')
            ->whereNotNull('solicitante')
            ->where('solicitante', '<>', '')
            ->distinct()->orderBy('solicitante')
            ->pluck('solicitante');

        return response()->json($unidades);
    }

    public function personas()
    {
        $personas = DB::table('despachos')
            ->whereNull('deleted_at')
            ->where('estado', 'DESPACHADO')
            ->whereNotNull('personal_recepcion')
            ->where('personal_recepcion', '<>', '')
            ->distinct()->orderBy('personal_recepcion')
            ->pluck('personal_recepcion');

        return response()->json($personas);
    }

    public function index(Request $request)
    {
        $rows = $this->baseQuery($request)->get();

        $totalMonto    = $rows->sum(fn ($r) => (float) $r->total_monto);
        $totalCantidad = $rows->sum(fn ($r) => (int) $r->cantidad_total);

        return response()->json([
            'rows' => $rows,
            'summary' => [
                'total_items'    => $rows->count(),
                'total_cantidad' => $totalCantidad,
                'total_monto'    => round($totalMonto, 2),
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $rows      = $this->baseQuery($request)->get();
        $dateFrom  = $request->get('date_from', 'inicio');
        $dateTo    = $request->get('date_to', 'fin');
        $unidad    = $request->get('solicitante', 'Todas las unidades');

        $totalMonto    = $rows->sum(fn ($r) => (float) $r->total_monto);
        $totalCantidad = $rows->sum(fn ($r) => (int) $r->cantidad_total);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte por Unidad');
        $lastCol = 'G';

        // Fila 1: Título
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'REPORTE DE MATERIAL DESPACHADO POR UNIDAD');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B5E20']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Fila 2: Info
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', "Unidad: {$unidad}    |    Rango: {$dateFrom} — {$dateTo}    |    Items: {$rows->count()}    |    Cantidad: {$totalCantidad}    |    Total Bs: " . number_format($totalMonto, 2));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F5E9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(15);

        // Fila 3: Encabezados
        $headers = ['A' => '#', 'B' => 'Producto', 'C' => 'Unidad de Medida', 'D' => 'Cantidad', 'E' => 'Precio Unit. (Bs)', 'F' => 'Total (Bs)', 'G' => 'Personas que retiraron'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}3", $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setAutoFilter("A3:{$lastCol}3");
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E7D32']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF388E3C']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(15);

        // Datos
        $rowNum = 4;
        foreach ($rows as $i => $item) {
            $bg = $i % 2 === 0 ? 'FFFFFFFF' : 'FFF1F8E9';

            $sheet->setCellValue("A{$rowNum}", $i + 1);
            $sheet->setCellValue("B{$rowNum}", $item->item_nombre);
            $sheet->setCellValue("C{$rowNum}", $item->unidad_medida);
            $sheet->setCellValue("D{$rowNum}", (int) $item->cantidad_total);
            $sheet->setCellValue("E{$rowNum}", (float) $item->precio_promedio);
            $sheet->setCellValue("F{$rowNum}", (float) $item->total_monto);
            $sheet->setCellValue("G{$rowNum}", $item->personas_recepcion ?? '');

            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            ]);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNum}:F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        // Totales
        $sheet->mergeCells("A{$rowNum}:C{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->setCellValue("D{$rowNum}", $totalCantidad);
        $sheet->setCellValue("F{$rowNum}", $totalMonto);
        $sheet->getColumnDimension('G')->setWidth(40);
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1B5E20']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("D{$rowNum}:F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('B')->setWidth(35);

        $filename = "reporte_unidad_{$dateFrom}_{$dateTo}.xlsx";
        $path     = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $rows     = $this->baseQuery($request)->get();
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $unidad   = $request->get('solicitante', 'Todas las unidades');

        $totalMonto    = $rows->sum(fn ($r) => (float) $r->total_monto);
        $totalCantidad = $rows->sum(fn ($r) => (int) $r->cantidad_total);

        $pdf = Pdf::loadView('reportes.almacen_unidad', [
            'rows'          => $rows,
            'dateFrom'      => $dateFrom,
            'dateTo'        => $dateTo,
            'unidad'        => $unidad,
            'totalMonto'    => $totalMonto,
            'totalCantidad' => $totalCantidad,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("reporte_unidad_{$dateFrom}_{$dateTo}.pdf");
    }

    private function baseQuery(Request $request)
    {
        $dateFrom          = $request->get('date_from');
        $dateTo            = $request->get('date_to');
        $solicitante       = $request->get('solicitante');
        $personalRecepcion = $request->get('personal_recepcion');

        return DB::table('despacho_detalles as dd')
            ->join('despachos as d', 'd.id', '=', 'dd.despacho_id')
            ->leftJoin('almacen_items as ai', 'ai.id', '=', 'dd.almacen_item_id')
            ->whereNull('d.deleted_at')
            ->whereNull('dd.deleted_at')
            ->where('d.estado', 'DESPACHADO')
            ->when($solicitante,       fn ($q) => $q->where('d.solicitante', $solicitante))
            ->when($personalRecepcion, fn ($q) => $q->where('d.personal_recepcion', $personalRecepcion))
            ->when($dateFrom,          fn ($q) => $q->whereDate('d.fecha_entrega', '>=', $dateFrom))
            ->when($dateTo,            fn ($q) => $q->whereDate('d.fecha_entrega', '<=', $dateTo))
            ->groupBy('dd.almacen_item_id', 'ai.nombre', 'dd.descripcion', 'dd.unidad', 'ai.unidad_medida', 'ai.imagen')
            ->select(
                'dd.almacen_item_id',
                DB::raw("COALESCE(NULLIF(ai.nombre,''), dd.descripcion, 'SIN NOMBRE') as item_nombre"),
                DB::raw("COALESCE(ai.unidad_medida, dd.unidad, '-') as unidad_medida"),
                'ai.imagen',
                DB::raw('SUM(dd.cantidad) as cantidad_total'),
                DB::raw('ROUND(AVG(dd.precio_unitario), 4) as precio_promedio'),
                DB::raw('ROUND(SUM(dd.total), 2) as total_monto'),
                DB::raw("GROUP_CONCAT(DISTINCT d.personal_recepcion ORDER BY d.personal_recepcion SEPARATOR ', ') as personas_recepcion")
            )
            ->orderByDesc('total_monto');
    }
}
