<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// Layout (empty arrays are skipped — do NOT use them):
// Row 1 : DGCF-R1.06 code + version
// Row 2 : Hospital name (merged A2:L2)
// Row 3 : Title (merged)
// Row 4 : Period (merged)
// Row 5 : Currency note (merged)
// Row 6 : Main headers — Nº, Descripción, Unidad, Precio | Cantidad (E6:H6) | Valores (I6:L6)
// Row 7 : Sub-headers — Saldo Ini, Entradas, Salidas, Saldo Fin × 2
// Row 8…N : Data rows
// Row N+1 : Totals row

class DetalleAlmacenExport implements FromArray, WithTitle, WithColumnWidths, WithStyles, WithEvents
{
    private const DATA_START = 8;

    public function __construct(
        private array $rows,
        private array $meta,
    ) {}

    public function array(): array
    {
        $periodo = $this->meta['periodo'] ?: 'DEL 1 DE ENERO AL 31 DE DICIEMBRE';

        $data = [
            // Row 1
            ['DGCF-R1.06', '', '', '', '', '', '', '', '', '', '', 'Versión 01'],
            // Row 2
            ['HOSPITAL GENERAL SAN JUAN DE DIOS ORURO'],
            // Row 3
            ['DETALLE DE ALMACENES-FARMACIA (BIENES DE CONSUMO)'],
            // Row 4
            [$periodo],
            // Row 5
            ['(Expresado en Bolivianos)'],
            // Row 6 — main headers (Cantidad spans E-H, Valores spans I-L)
            ['Nº', 'Descripción (Item)', 'Unidad de medida', 'Precio Unitario',
             'Cantidad', '', '', '',
             'Valores', '', '', ''],
            // Row 7 — sub-headers
            ['', '', '', '',
             'Saldo Inicial', 'Entradas', 'Salidas', 'Saldo Final',
             'Saldo Inicial', 'Entradas', 'Salidas', 'Saldo Final'],
        ];

        $totCantIni = $totCantEnt = $totCantSal = $totCantFin = 0;
        $totValIni  = $totValEnt  = $totValSal  = $totValFin  = 0.0;

        foreach ($this->rows as $row) {
            $data[] = [
                $row['nro'],
                $row['descripcion'],
                $row['unidad'],
                $row['precio_unitario'],
                $row['cant_saldo_ini'],
                $row['cant_entradas'],
                $row['cant_salidas'],
                $row['cant_saldo_final'],
                $row['val_saldo_ini'],
                $row['val_entradas'],
                $row['val_salidas'],
                $row['val_saldo_final'],
            ];
            $totCantIni += $row['cant_saldo_ini'];
            $totCantEnt += $row['cant_entradas'];
            $totCantSal += $row['cant_salidas'];
            $totCantFin += $row['cant_saldo_final'];
            $totValIni  += $row['val_saldo_ini'];
            $totValEnt  += $row['val_entradas'];
            $totValSal  += $row['val_salidas'];
            $totValFin  += $row['val_saldo_final'];
        }

        $data[] = [
            '', 'TOTAL', '', '',
            $totCantIni, $totCantEnt, $totCantSal, $totCantFin,
            round($totValIni, 2), round($totValEnt, 2), round($totValSal, 2), round($totValFin, 2),
        ];

        return $data;
    }

    public function title(): string { return 'DGCF-R1.06 Detalle'; }

    public function columnWidths(): array
    {
        return [
            'A' => 5, 'B' => 45, 'C' => 12, 'D' => 14,
            'E' => 14, 'F' => 13, 'G' => 13, 'H' => 14,
            'I' => 16, 'J' => 15, 'K' => 15, 'L' => 16,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['size' => 9]],
            2 => ['font' => ['bold' => true, 'size' => 11],
                  'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['bold' => true, 'size' => 10],
                  'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            4 => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            5 => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (['A2:L2', 'A3:L3', 'A4:L4', 'A5:L5'] as $range) {
                    $sheet->mergeCells($range);
                }

                // Group headers span row 6
                $sheet->mergeCells('E6:H6');
                $sheet->mergeCells('I6:L6');
                // Fixed columns (A-D) span rows 6-7
                foreach (['A', 'B', 'C', 'D'] as $col) {
                    $sheet->mergeCells("{$col}6:{$col}7");
                }

                $headerStyle = [
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F5EA8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical'   => Alignment::VERTICAL_CENTER,
                                    'wrapText'   => true],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                      'color'       => ['rgb' => '0A4A8A']]],
                ];
                $sheet->getStyle('A6:L7')->applyFromArray($headerStyle);
                $sheet->getRowDimension(6)->setRowHeight(22);
                $sheet->getRowDimension(7)->setRowHeight(22);

                // Group color overrides
                $sheet->getStyle('E6:H7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1A73C4');
                $sheet->getStyle('I6:L7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('156A3C');

                $dataStart = self::DATA_START;
                $dataEnd   = $dataStart + count($this->rows) - 1;
                $totalRow  = $dataEnd + 1;

                if ($dataEnd >= $dataStart) {
                    $sheet->getStyle("A{$dataStart}:L{$dataEnd}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                        'color'       => ['rgb' => 'B8D0EA']]],
                        'font'    => ['size' => 8],
                    ]);
                    for ($r = $dataStart; $r <= $dataEnd; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:L{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F9FF');
                        }
                    }
                    $sheet->getStyle("D{$dataStart}:L{$dataEnd}")->getNumberFormat()
                          ->setFormatCode('#,##0.00');
                }

                // Totals row: label (A-D), quantity blue (E-H), values yellow (I-L)
                $sheet->getStyle("A{$totalRow}:D{$totalRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                      'color'       => ['rgb' => '0A4A8A']]],
                ]);
                $sheet->getStyle("E{$totalRow}:H{$totalRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F5EA8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                      'color'       => ['rgb' => '0A4A8A']]],
                ]);
                $sheet->getStyle("E{$totalRow}:H{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getStyle("I{$totalRow}:L{$totalRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD700']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM,
                                                      'color'       => ['rgb' => 'B8860B']]],
                ]);
                $sheet->getStyle("I{$totalRow}:L{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                $sheet->getRowDimension($totalRow)->setRowHeight(18);
            },
        ];
    }
}
