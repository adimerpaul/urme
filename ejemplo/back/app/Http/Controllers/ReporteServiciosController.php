<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ReporteServiciosController extends Controller
{
    public function index(Request $request)
    {
        $rows = $this->baseQuery($request)->get();

        $totalMonto  = $rows->sum(fn ($r) => (float) $r->total_monto);
        $embarazadas = $rows->where('paciente_embarazo', 1)->count();

        return response()->json([
            'rows' => $rows,
            'summary' => [
                'total_solicitudes' => $rows->count(),
                'total_monto'       => round($totalMonto, 2),
                'embarazadas'       => $embarazadas,
            ],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $rows     = $this->baseQuery($request)->get();
        $dateFrom = $request->get('date_from', 'inicio');
        $dateTo   = $request->get('date_to', 'fin');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Solicitudes');

        $lastCol = 'O';

        // ── Fila 1: Título ────────────────────────────────────────────────
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'REPORTE DE SOLICITUDES POR SERVICIOS / PRESTACIONES');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A237E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Fila 2: Rango y totales ───────────────────────────────────────
        $sheet->mergeCells("A2:{$lastCol}2");
        $totalMonto = $rows->sum(fn ($r) => (float) $r->total_monto);
        $sheet->setCellValue('A2', "Rango: {$dateFrom} — {$dateTo}    |    Total solicitudes: {$rows->count()}    |    Total Bs: " . number_format($totalMonto, 2));
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF333333']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8EAF6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(16);

        // ── Fila 3: Encabezados ───────────────────────────────────────────
        $headers = [
            'A' => 'ID',
            'B' => 'Código',
            'C' => 'Fecha',
            'D' => 'Paciente',
            'E' => 'CI',
            'F' => 'Edad',
            'G' => 'Género',
            'H' => 'Embarazada',
            'I' => 'Cama',
            'J' => 'Sala',
            'K' => 'Servicios',
            'L' => 'Prestaciones (Áreas)',
            'M' => 'Total Bs',
            'N' => 'Estado',
            'O' => 'Doctor',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}3", $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setAutoFilter("A3:{$lastCol}3");

        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF283593']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFAAAAAA']]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(16);

        // ── Filas de datos ────────────────────────────────────────────────
        $rowNum = 4;
        foreach ($rows as $i => $item) {
            $isEven  = $i % 2 === 0;
            $bgColor = $isEven ? 'FFFFFFFF' : 'FFF5F5F5';

            $sheet->setCellValue("A{$rowNum}", $item->id);
            $sheet->setCellValue("B{$rowNum}", $item->codigo_solicitud ?? '');
            $sheet->setCellValue("C{$rowNum}", $item->fecha_solicitud);
            $sheet->setCellValue("D{$rowNum}", $item->paciente_nombre);
            $sheet->setCellValue("E{$rowNum}", $item->paciente_ci);
            $sheet->setCellValue("F{$rowNum}", (int) $item->paciente_edad);
            $sheet->setCellValue("G{$rowNum}", $item->paciente_genero);
            $sheet->setCellValue("H{$rowNum}", $item->paciente_embarazo ? 'Sí' : 'No');
            $sheet->setCellValue("I{$rowNum}", $item->cama);
            $sheet->setCellValue("J{$rowNum}", $item->sala);
            $sheet->setCellValue("K{$rowNum}", $item->servicios_nombres);
            $sheet->setCellValue("L{$rowNum}", $item->areas_nombres);
            $sheet->setCellValue("M{$rowNum}", (float) $item->total_monto);
            $sheet->setCellValue("N{$rowNum}", $item->estado);
            $sheet->setCellValue("O{$rowNum}", $item->doctor_nombre);

            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
            ]);

            // Color de género
            $genero = $item->paciente_genero;
            if ($genero === 'M') {
                $sheet->getStyle("G{$rowNum}")->getFont()->getColor()->setARGB('FF1565C0');
            } elseif ($genero === 'F') {
                $sheet->getStyle("G{$rowNum}")->getFont()->getColor()->setARGB('FFAD1457');
            }

            // Embarazada en morado
            if ($item->paciente_embarazo) {
                $sheet->getStyle("H{$rowNum}")->getFont()->getColor()->setARGB('FF6A1B9A');
                $sheet->getStyle("H{$rowNum}")->getFont()->setBold(true);
            }

            // Alineación centrada para columnas cortas
            $sheet->getStyle("A{$rowNum}:C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}:J{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $rowNum++;
        }

        // ── Fila de totales ───────────────────────────────────────────────
        $sheet->mergeCells("A{$rowNum}:L{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->setCellValue("M{$rowNum}", $totalMonto);
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1A237E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("M{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Anchos fijos para columnas de texto largo ─────────────────────
        $sheet->getColumnDimension('K')->setWidth(40);
        $sheet->getColumnDimension('L')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(28);
        $sheet->getColumnDimension('O')->setWidth(22);

        $filename = "solicitudes_{$dateFrom}_{$dateTo}.xlsx";
        $path     = storage_path("app/{$filename}");

        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function exportExcelMensual(Request $request)
    {
        ini_set('memory_limit', '256M');

        $dateFrom = $request->get('date_from', date('Y-m-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-t'));

        // Solicitudes del período con datos de quimica_sanguinea (serología VIH/RPR/HVB/HVC)
        $rows = $this->baseQuery($request)->get();

        // Obtener resultados serológicos indexados por solicitude_id
        $solicitudeIds = $rows->pluck('id')->toArray();
        $serologias = \Illuminate\Support\Facades\DB::table('quimica_sanguineas')
            ->whereIn('solicitude_id', $solicitudeIds)
            ->whereNull('deleted_at')
            ->select([
                'solicitude_id',
                'prueba_rapida_vih',
                'rpr',
                'prueba_rapida_hepatitis_b',
                'prueba_rapida_hepatitis_c',
                'prueba_rapida_sifilis',
            ])
            ->get()
            ->keyBy('solicitude_id');

        $templatePath = storage_path('app/templates/registro_vih_mensual.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);

        // Nombre del mes en español
        $meses = [
            1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',
            7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE',
        ];
        $mes = $dateFrom ? $meses[(int) date('n', strtotime($dateFrom))] : '';

        // Filas de datos: primera fila = 14, hasta fila 33 (20 filas por hoja)
        $firstDataRow = 14;
        $rowsPerSheet = 20;

        $chunks = $rows->chunk($rowsPerSheet);

        foreach ($chunks as $sheetIndex => $chunk) {
            if ($sheetIndex >= $spreadsheet->getSheetCount()) {
                break; // solo usamos las hojas que trae el template
            }

            $sheet = $spreadsheet->getSheet($sheetIndex);

            // Actualizar mes en la celda V9
            $sheet->setCellValue('V9', $mes);

            $rowNum = $firstDataRow;
            foreach ($chunk as $item) {
                $sero = $serologias->get($item->id);

                // ── Datos del paciente ─────────────────────────────────────
                $sheet->setCellValue("C{$rowNum}", $item->fecha_solicitud);
                $sheet->setCellValue("D{$rowNum}", $item->paciente_nombre);
                $sheet->setCellValue("N{$rowNum}", $item->codigo_solicitud ?? '');
                $sheet->setCellValue("R{$rowNum}", $item->paciente_ci);

                // Edad en columna V (masculino) o W (femenino)
                $edad = (int) ($item->paciente_edad ?? 0);
                if ($item->paciente_genero === 'M') {
                    $sheet->setCellValue("V{$rowNum}", $edad);
                } elseif ($item->paciente_genero === 'F') {
                    $sheet->setCellValue("W{$rowNum}", $edad);
                }

                // Embarazada → marca X en columna X
                if ($item->paciente_embarazo) {
                    $sheet->setCellValue("X{$rowNum}", 'X');
                }

                // ── Resultados serológicos ─────────────────────────────────
                if ($sero) {
                    $reactivo = fn($val) => strtolower(trim((string)($val ?? ''))) === 'reactivo';

                    // VIH 1ª prueba rápida: AE=prueba hecha, AF=reactivo
                    if ($sero->prueba_rapida_vih !== null) {
                        $sheet->setCellValue("AE{$rowNum}", 'X');
                        if ($reactivo($sero->prueba_rapida_vih)) {
                            $sheet->setCellValue("AF{$rowNum}", 'X');
                        }
                    }

                    // Sifilis (RPR o prueba rápida): AM=prueba hecha, AN=reactivo
                    $sifilisVal = $sero->prueba_rapida_sifilis ?? $sero->rpr;
                    if ($sifilisVal !== null) {
                        $sheet->setCellValue("AM{$rowNum}", 'X');
                        if ($reactivo($sifilisVal)) {
                            $sheet->setCellValue("AN{$rowNum}", 'X');
                        }
                    }

                    // HVB: AQ=prueba hecha, AR=reactivo
                    if ($sero->prueba_rapida_hepatitis_b !== null) {
                        $sheet->setCellValue("AQ{$rowNum}", 'X');
                        if ($reactivo($sero->prueba_rapida_hepatitis_b)) {
                            $sheet->setCellValue("AR{$rowNum}", 'X');
                        }
                    }

                    // HVC: AS=prueba hecha, AT=reactivo
                    if ($sero->prueba_rapida_hepatitis_c !== null) {
                        $sheet->setCellValue("AS{$rowNum}", 'X');
                        if ($reactivo($sero->prueba_rapida_hepatitis_c)) {
                            $sheet->setCellValue("AT{$rowNum}", 'X');
                        }
                    }
                }

                $rowNum++;
            }
        }

        $filename = "registro_vih_{$dateFrom}_{$dateTo}.xlsx";
        $path     = storage_path("app/{$filename}");

        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function exportPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $rows        = $this->baseQuery($request)->get();
        $totalMonto  = $rows->sum(fn ($r) => (float) $r->total_monto);
        $embarazadas = $rows->where('paciente_embarazo', 1)->count();

        $pdf = Pdf::loadView('reportes.servicios_resumen', [
            'rows'        => $rows,
            'dateFrom'    => $dateFrom,
            'dateTo'      => $dateTo,
            'totalMonto'  => $totalMonto,
            'embarazadas' => $embarazadas,
            'totalCount'  => $rows->count(),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream("solicitudes_{$dateFrom}_{$dateTo}.pdf");
    }

    public function exportPdfEnts(Request $request)
    {
        $data = $this->buildEntsData($request);
        $pdf  = Pdf::loadView('reportes.ents_quimica', $data)->setPaper('letter', 'portrait');
        return $pdf->stream("ents_quimica_{$data['dateFrom']}_{$data['dateTo']}.pdf");
    }

    public function exportExcelEnts(Request $request)
    {
        $data      = $this->buildEntsData($request);
        $grupos    = $data['grupos'];
        $totalSus  = $data['totalSus'];
        $totalExt  = $data['totalExternos'];
        $totalGen  = $data['totalGeneral'];
        $mes       = $data['mes'];
        $gestion   = $data['gestion'];
        $dateFrom  = $data['dateFrom'];
        $dateTo    = $data['dateTo'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ENTs Química');

        $gris  = 'FF4A4A4A';
        $negro = 'FF000000';
        $blanc = 'FFFFFFFF';
        $azul  = 'FF1A237E';

        // ── Cabecera institucional ─────────────────────────────────────
        foreach (['A','B','C','D','E'] as $col) {
            $sheet->getColumnDimension($col)->setWidth($col === 'B' ? 38 : ($col === 'C' ? 26 : 14));
        }

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'HOSPITAL GENERAL SAN JUAN DE DIOS');
        $sheet->getStyle('A1')->applyFromArray(['font'=>['bold'=>true,'size'=>11],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'SERVICIO DE LABORATORIO DE ANALISIS CLINICO MICROBIOLÓGICO');
        $sheet->getStyle('A2')->applyFromArray(['font'=>['size'=>9],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', 'INFORME DE PRODUCCIÓN Y VIGILANCIA DE ENTs');
        $sheet->getStyle('A3')->applyFromArray(['font'=>['bold'=>true,'size'=>12,'color'=>['argb'=>$azul]],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]]);

        $sheet->mergeCells('A4:E4');
        $sheet->setCellValue('A4', 'AREA QUÍMICA SANGUÍNEA');
        $sheet->getStyle('A4')->applyFromArray(['font'=>['bold'=>true,'size'=>11,'color'=>['argb'=>$azul]],'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER]]);

        // ── Fila mes / gestión ──────────────────────────────────────────
        $sheet->setCellValue('A6', 'MES:');
        $sheet->setCellValue('B6', $mes);
        $sheet->setCellValue('C6', 'GESTIÓN:');
        $sheet->setCellValue('D6', $gestion);
        $sheet->getStyle('A6:D6')->applyFromArray(['font'=>['bold'=>true]]);

        $sheet->setCellValue('A7', 'TOTAL PACIENTES ATENDIDOS SUS:');
        $sheet->setCellValue('C7', $totalSus);
        $sheet->setCellValue('D7', 'TOTAL');
        $sheet->setCellValue('E7', $totalGen);
        $sheet->getStyle('A7:E7')->applyFromArray(['font'=>['bold'=>true]]);

        $sheet->setCellValue('A8', 'TOTAL PACIENTES ATENDIDOS EXTERNOS:');
        $sheet->setCellValue('C8', $totalExt);
        $sheet->getStyle('A8')->applyFromArray(['font'=>['bold'=>true]]);

        // ── Título tabla ───────────────────────────────────────────────
        $sheet->mergeCells('A10:E10');
        $sheet->setCellValue('A10', 'PRUEBAS PARA ENFERMEDADES NO TRANSMISIBLES');
        $sheet->getStyle('A10')->applyFromArray([
            'font'      => ['bold'=>true,'size'=>9,'color'=>['argb'=>$blanc]],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>$gris]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(10)->setRowHeight(16);

        // ── Cabeceras columnas ─────────────────────────────────────────
        $sheet->mergeCells('A11:B11');
        $sheet->setCellValue('A11', 'PRUEBAS DE DIAGNÓSTICO');
        $sheet->setCellValue('C11', 'VALORES DE REFERENCIA UTILIZADOS');
        $sheet->setCellValue('D11', 'POR ENCIMA DE LOS VALORES DE REFERENCIA');
        $sheet->setCellValue('E11', 'POR DEBAJO DE LOS VALORES DE REFERENCIA');

        $sheet->getStyle('A11:E11')->applyFromArray([
            'font'      => ['bold'=>true,'size'=>8,'color'=>['argb'=>$blanc]],
            'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>$gris]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FFAAAAAA']]],
        ]);
        $sheet->getRowDimension(11)->setRowHeight(28);

        // ── Filas de datos ─────────────────────────────────────────────
        $rowNum = 12;
        foreach ($grupos as $grupoLabel => $items) {
            $startRow = $rowNum;
            foreach ($items as $item) {
                $sheet->setCellValue("B{$rowNum}", $item['label']);
                $sheet->setCellValue("C{$rowNum}", $item['ref']);
                if ($item['cnt'][0] !== '') $sheet->setCellValue("D{$rowNum}", $item['cnt'][0]);
                if ($item['cnt'][1] !== '') $sheet->setCellValue("E{$rowNum}", $item['cnt'][1]);

                $bg = ($rowNum % 2 === 0) ? 'FFFAFAFA' : 'FFFFFFFF';
                $sheet->getStyle("A{$rowNum}:E{$rowNum}")->applyFromArray([
                    'fill'    => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>$bg]],
                    'borders' => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN,'color'=>['argb'=>'FFDDDDDD']]],
                    'font'    => ['size'=>8],
                ]);
                $sheet->getStyle("C{$rowNum}:E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $rowNum++;
            }
            $endRow = $rowNum - 1;
            if ($endRow >= $startRow) {
                $sheet->mergeCells("A{$startRow}:A{$endRow}");
            }
            $sheet->setCellValue("A{$startRow}", $grupoLabel);
            $sheet->getStyle("A{$startRow}")->applyFromArray([
                'font'      => ['bold'=>true,'size'=>8,'color'=>['argb'=>$blanc]],
                'fill'      => ['fillType'=>Fill::FILL_SOLID,'startColor'=>['argb'=>$gris]],
                'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER,'vertical'=>Alignment::VERTICAL_CENTER,'wrapText'=>true],
            ]);
        }

        $filename = "ents_quimica_{$dateFrom}_{$dateTo}.xlsx";
        $path     = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);
        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function buildEntsData(Request $request): array
    {
        $dateFrom = $request->get('date_from', date('Y-m-01'));
        $dateTo   = $request->get('date_to',   date('Y-m-t'));

        $rows = DB::table('quimica_sanguineas as q')
            ->join('solicitudes as s', 's.id', '=', 'q.solicitude_id')
            ->whereNull('q.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, fn ($q) => $q->whereDate('s.fecha_solicitud', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('s.fecha_solicitud', '<=', $dateTo))
            ->select(
                'q.glucosa','q.hb_glicosilada','q.hb_a1c',
                'q.tolerancia_glucosa_1h',
                'q.colesterol_total','q.trigliceridos','q.hdl_colesterol','q.ldl_colesterol',
                'q.creatinina','q.urea','q.nus','q.sodio','q.potasio','q.cloro',
                'q.proteinas_totales','q.albumina','q.calcio',
                'q.creatinuria_24h','q.dce','q.proteinuria_24h',
                'q.got','q.gpt','q.fosfatasa_alcalina','q.ggt','q.bilirrubina_total',
                'q.hierro_serico','q.ferritina',
                'q.ck_mb','q.ck_total','q.prueba_rapida_troponina',
                'q.amilasa','q.lipasa','q.ldh','q.acido_urico',
                's.tipo_atencion'
            )
            ->get();

        $totalSus      = $rows->where('tipo_atencion', 'SI')->count();
        $totalExternos = $rows->where('tipo_atencion', '!=', 'SI')->count();
        $totalGeneral  = $rows->count();

        $cnt = function ($col, $min, $max, $tipo = 'numero') use ($rows) {
            $vals = $rows->whereNotNull($col)->pluck($col);
            if ($tipo === 'texto') {
                $enc = $vals->filter(fn ($v) => strtoupper(trim((string) $v)) !== 'NEGATIVO' && trim((string) $v) !== '')->count();
                return [$enc ?: '', ''];
            }
            $nums = $vals->map(fn ($v) => (float) $v);
            $enc  = $max !== null ? $nums->filter(fn ($v) => $v > $max)->count() : '';
            $deb  = $min !== null ? $nums->filter(fn ($v) => $v < $min)->count() : '';
            return [$enc !== '' && $enc > 0 ? $enc : '', $deb !== '' && $deb > 0 ? $deb : ''];
        };

        // hb_glicosilada: combina ambas columnas
        $hbVals = $rows->map(fn ($r) => $r->hb_glicosilada ?? $r->hb_a1c ?? null)
                       ->filter(fn ($v) => $v !== null)
                       ->map(fn ($v) => (float) $v);
        $hbCnt  = [$hbVals->filter(fn ($v) => $v > 5.8)->count() ?: '', $hbVals->filter(fn ($v) => $v < 3.8)->count() ?: ''];

        $grupos = [
            'PERFIL DIABÉTICO' => [
                ['label' => '1. Glucosa',                          'cnt' => $cnt('glucosa', 70, 105),         'ref' => '70-105 mg/dl'],
                ['label' => '2. Glucosa post prandial',            'cnt' => ['', ''],                         'ref' => 'mayor a 120 mg/dl'],
                ['label' => '3. Hemoglobina glicosilada (A1c)',    'cnt' => $hbCnt,                           'ref' => '3.8 a 5.8 %'],
                ['label' => '4. Curva de Tolerancia a la Glucosa', 'cnt' => ['', ''],                         'ref' => ''],
            ],
            'PERFIL LIPÍDICO' => [
                ['label' => '5. Colesterol',                       'cnt' => $cnt('colesterol_total', null, 200), 'ref' => 'hasta 200 mg/dl'],
                ['label' => '6. Triglicéridos',                    'cnt' => $cnt('trigliceridos', null, 150),    'ref' => 'hasta 150 mg/dl'],
                ['label' => '7. HDL-Colesterol',                   'cnt' => $cnt('hdl_colesterol', 35, null),    'ref' => 'mayor a 35 mg/dl'],
                ['label' => '8. LDL-Colesterol',                   'cnt' => $cnt('ldl_colesterol', null, 129),   'ref' => 'menor a 129 mg/dl'],
            ],
            'PERFIL RENAL' => [
                ['label' => '9. Creatinina',                           'cnt' => $cnt('creatinina', 0.7, 1.2),       'ref' => '0.7 a 1.2 mg/dl'],
                ['label' => '10. UREA/NUS',                            'cnt' => $cnt('nus', 15, 39),                 'ref' => '15-39 mg/dl'],
                ['label' => '11. Electrolitos (Sodio, Potasio, Cloro)','cnt' => $cnt('sodio', 135, 148),            'ref' => '135 a 148 / 3.5 a 5.3 / 98 a 107'],
                ['label' => '12. Proteínas totales',                   'cnt' => $cnt('proteinas_totales', 6.0, 7.8),'ref' => '6.0 a 7.8 g/dl'],
                ['label' => '13. Albúmina',                            'cnt' => $cnt('albumina', 3.4, 4.8),         'ref' => '3.4 a 4.8 g/dl'],
                ['label' => '14. Calcio',                              'cnt' => $cnt('calcio', 8.6, 10.3),          'ref' => '8.6 a 10.3 mg/dl'],
                ['label' => '15. Examen general de orina',             'cnt' => ['', ''],                           'ref' => ''],
                ['label' => '16. Microalbuminuria',                    'cnt' => ['', ''],                           'ref' => ''],
                ['label' => '17. Creatinuria de 24 horas',             'cnt' => ['', ''],                           'ref' => 'g/24 horas'],
                ['label' => '18. Depuración de creatinina',            'cnt' => ['', ''],                           'ref' => ''],
                ['label' => '19. Proteinuria de 24 horas',             'cnt' => ['', ''],                           'ref' => 'g/24 horas'],
            ],
            'PERFIL HEPÁTICO' => [
                ['label' => '20. Transaminasa GOT',                    'cnt' => $cnt('got', null, 40),              'ref' => 'hasta 40 U/L'],
                ['label' => '21. Transaminasa GPT',                    'cnt' => $cnt('gpt', null, 41),              'ref' => 'hasta 41 U/L'],
                ['label' => '22. Fosfatasa Alcalina',                  'cnt' => $cnt('fosfatasa_alcalina', null, 105),'ref' => 'mayor a 105 U/L'],
                ['label' => '23. Gamma Glutamil Transferasa (GGT)',    'cnt' => $cnt('ggt', 5, 39),                 'ref' => '5 a 39 U/L'],
                ['label' => '24. Bilirrubinas (T, D, I)',              'cnt' => $cnt('bilirrubina_total', null, 0.8),'ref' => 'hasta 0.2 hasta 0.8 mg/dl'],
            ],
            'INDICADORES DE ANEMIAS' => [
                ['label' => '25. Hierro',       'cnt' => $cnt('hierro_serico', 50, 170), 'ref' => '50 a 170 ug/dl'],
                ['label' => '26. TIBC',         'cnt' => ['', ''],                       'ref' => ''],
                ['label' => '27. Ferritina',    'cnt' => ['', ''],                       'ref' => ''],
                ['label' => '28. Vitamina B12', 'cnt' => ['', ''],                       'ref' => ''],
            ],
            'PERFIL CARDIACO' => [
                ['label' => '31. CK-MB',      'cnt' => $cnt('ck_mb', 0, 24),                                'ref' => '0-24 U/L'],
                ['label' => '32. CK',         'cnt' => $cnt('ck_total', null, 145),                         'ref' => 'menor a 145 U/L'],
                ['label' => '33. Troponina I','cnt' => $cnt('prueba_rapida_troponina', null, null, 'texto'), 'ref' => 'NEGATIVO'],
                ['label' => '34. NT-proBNP',  'cnt' => ['', ''],                                            'ref' => ''],
                ['label' => '35. Otros',       'cnt' => ['', ''],                                           'ref' => ''],
            ],
            'VARIOS' => [
                ['label' => '50. Amilasa',               'cnt' => $cnt('amilasa', 22, 80),      'ref' => '22-80 U/L'],
                ['label' => '51. Lipasa',                'cnt' => $cnt('lipasa', null, 150),    'ref' => 'HASTA 150 U/L'],
                ['label' => '52. Lactato Deshidrogenasa','cnt' => $cnt('ldh', 200, 730),        'ref' => '200 A 730 U/L'],
                ['label' => '53. Ácido Úrico',           'cnt' => $cnt('acido_urico', 2.6, 7.2),'ref' => '2.6 A 7.2 mg/dl'],
            ],
        ];

        $meses    = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',
                     7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
        $mes      = $dateFrom ? ($meses[(int) date('n', strtotime($dateFrom))] ?? '') : '';
        $gestion  = $dateFrom ? date('Y', strtotime($dateFrom)) : date('Y');

        return compact('grupos', 'totalSus', 'totalExternos', 'totalGeneral', 'mes', 'gestion', 'dateFrom', 'dateTo');
    }

    private function baseQuery(Request $request)
    {
        $dateFrom   = $request->get('date_from');
        $dateTo     = $request->get('date_to');
        $servicioId = $request->get('servicio_id');
        $areaId     = $request->get('area_id');
        $genero     = $request->get('genero');
        $embarazada = $request->get('embarazada'); // '1' | '0' | null
        $cama       = $request->get('cama');
        $paciente   = $request->get('paciente');

        return DB::table('solicitudes as s')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, fn ($q) => $q->whereDate('s.fecha_solicitud', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('s.fecha_solicitud', '<=', $dateTo))
            ->when($genero,   fn ($q) => $q->where('s.paciente_genero', $genero))
            ->when(
                $cama !== null && $cama !== '',
                fn ($q) => $q->where('s.cama', 'like', "%{$cama}%")
            )
            ->when(
                $paciente !== null && $paciente !== '',
                fn ($q) => $q->where(function ($qq) use ($paciente) {
                    $qq->where('s.paciente_nombre', 'like', "%{$paciente}%")
                       ->orWhere('s.paciente_ci', 'like', "%{$paciente}%");
                })
            )
            ->when(
                $embarazada !== null && $embarazada !== '',
                fn ($q) => $q->where('s.paciente_embarazo', (bool) (int) $embarazada)
            )
            ->when($servicioId, fn ($q) => $q->whereExists(function ($sub) use ($servicioId) {
                $sub->from('servicio_solicitudes as ss')
                    ->whereColumn('ss.solicitude_id', 's.id')
                    ->whereNull('ss.deleted_at')
                    ->where('ss.servicio_id', $servicioId);
            }))
            ->when($areaId, fn ($q) => $q->whereExists(function ($sub) use ($areaId) {
                $sub->from('servicio_solicitudes as ss')
                    ->whereColumn('ss.solicitude_id', 's.id')
                    ->whereNull('ss.deleted_at')
                    ->where('ss.area_id', $areaId);
            }))
            ->select(
                's.id',
                's.codigo',
                's.codigo_solicitud',
                's.fecha_solicitud',
                's.hora_solicitud',
                's.paciente_nombre',
                's.paciente_ci',
                's.paciente_edad',
                's.paciente_genero',
                's.paciente_embarazo',
                's.cama',
                's.sala',
                's.estado',
                's.doctor_nombre',
                DB::raw('u.name as usuario_nombre')
            )
            ->selectRaw("(
                SELECT GROUP_CONCAT(
                    DISTINCT COALESCE(NULLIF(se2.nombre,''), NULLIF(ss2.nombre,''), 'SIN NOMBRE')
                    ORDER BY se2.nombre SEPARATOR ' | '
                )
                FROM servicio_solicitudes ss2
                LEFT JOIN servicios se2 ON se2.id = ss2.servicio_id
                WHERE ss2.solicitude_id = s.id AND ss2.deleted_at IS NULL
            ) as servicios_nombres")
            ->selectRaw("(
                SELECT GROUP_CONCAT(
                    DISTINCT COALESCE(a2.name, '')
                    ORDER BY a2.name SEPARATOR ' | '
                )
                FROM servicio_solicitudes ss2
                LEFT JOIN areas a2 ON a2.id = ss2.area_id
                WHERE ss2.solicitude_id = s.id AND ss2.deleted_at IS NULL
            ) as areas_nombres")
            ->selectRaw("(
                SELECT ROUND(SUM(COALESCE(ss2.precio, 0)), 2)
                FROM servicio_solicitudes ss2
                WHERE ss2.solicitude_id = s.id AND ss2.deleted_at IS NULL
            ) as total_monto")
            ->selectRaw("(
                SELECT COUNT(ss2.id)
                FROM servicio_solicitudes ss2
                WHERE ss2.solicitude_id = s.id AND ss2.deleted_at IS NULL
            ) as total_items")
            ->orderByDesc('s.fecha_solicitud')
            ->orderByDesc('s.id');
    }
}
