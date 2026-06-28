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

// Layout (empty arrays are skipped by maatwebsite/excel — do NOT use them):
// Row 1 : DGCF-R1.05 code + version
// Row 2 : Hospital name (merged A2:G2)
// Row 3 : Title (merged)
// Row 4 : Period (merged)
// Row 5 : Currency note (merged)
// Row 6 : Column headers  ← blue header style
// Row 7…N : Data rows
// Row N+1 : Totals row

class ResumenAlmacenExport implements FromArray, WithTitle, WithColumnWidths, WithStyles, WithEvents
{
    private const DATA_START = 7;

    public function __construct(
        private array  $rows,
        private ?array $total,
        private array  $meta,
    ) {}

    public function array(): array
    {
        $periodo = $this->meta['periodo'] ?: 'DEL 01 DE ENERO AL 31 DE DICIEMBRE';
        $dFmt    = isset($this->meta['desde']) ? \Carbon\Carbon::parse($this->meta['desde'])->format('d/m/Y') : '01/01';
        $hFmt    = isset($this->meta['hasta']) ? \Carbon\Carbon::parse($this->meta['hasta'])->format('d/m/Y') : '31/12';

        $data = [
            // Row 1
            ['DGCF-R1.05', '', '', '', '', '', 'Versión 01'],
            // Row 2
            ['HOSPITAL GENERAL SAN JUAN DE DIOS ORURO'],
            // Row 3
            ['RESUMEN DE ALMACENES Y FARMACIA (BIENES DE CONSUMO)'],
            // Row 4
            [$periodo],
            // Row 5
            ['(Expresado en Bolivianos)'],
            // Row 6 — column headers
            ['Nº', 'DETALLE', 'Partida',
             "Cantidad Inicial al {$dFmt}",
             "Saldo Inicial al {$dFmt} (Bs)",
             "Cantidad Final al {$hFmt}",
             "Saldo Final al {$hFmt} (Bs)"],
        ];

        foreach ($this->rows as $row) {
            $data[] = [
                $row['nro'],
                $row['detalle'],
                $row['partida'],
                $row['cant_ini'],
                $row['saldo_ini'],
                $row['cant_final'],
                $row['saldo_final'],
            ];
        }

        if ($this->total) {
            $data[] = [
                '', 'TOTAL', '',
                $this->total['cant_ini'],
                $this->total['saldo_ini'],
                $this->total['cant_final'],
                $this->total['saldo_final'],
            ];
        }

        return $data;
    }

    public function title(): string { return 'DGCF-R1.05 Resumen'; }

    public function columnWidths(): array
    {
        return ['A' => 5, 'B' => 58, 'C' => 11, 'D' => 22, 'E' => 26, 'F' => 22, 'G' => 26];
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
            6 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F5EA8']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical'   => Alignment::VERTICAL_CENTER,
                                'wrapText'   => true],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                  'color'       => ['rgb' => '0A4A8A']]],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach (['A2:G2', 'A3:G3', 'A4:G4', 'A5:G5'] as $range) {
                    $sheet->mergeCells($range);
                }

                $sheet->getRowDimension(6)->setRowHeight(36);

                $dataStart = self::DATA_START;
                $dataEnd   = $dataStart + count($this->rows) - 1;
                $totalRow  = $this->total ? $dataEnd + 1 : null;

                if ($dataEnd >= $dataStart) {
                    $sheet->getStyle("A{$dataStart}:G{$dataEnd}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                        'color'       => ['rgb' => 'B8D0EA']]],
                        'font'    => ['size' => 9],
                    ]);
                    for ($r = $dataStart; $r <= $dataEnd; $r++) {
                        if ($r % 2 === 0) {
                            $sheet->getStyle("A{$r}:G{$r}")->getFill()
                                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F9FF');
                        }
                    }
                    $sheet->getStyle("D{$dataStart}:G{$dataEnd}")->getNumberFormat()
                          ->setFormatCode('#,##0.00');
                }

                if ($totalRow) {
                    $sheet->getStyle("A{$totalRow}:C{$totalRow}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 10],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                          'color'       => ['rgb' => '0A4A8A']]],
                    ]);
                    foreach (['D', 'F'] as $col) {
                        $sheet->getStyle("{$col}{$totalRow}")->applyFromArray([
                            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F5EA8']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN,
                                                              'color'       => ['rgb' => '0A4A8A']]],
                        ]);
                        $sheet->getStyle("{$col}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    foreach (['E', 'G'] as $col) {
                        $sheet->getStyle("{$col}{$totalRow}")->applyFromArray([
                            'font'      => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 11],
                            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFD700']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM,
                                                              'color'       => ['rgb' => 'B8860B']]],
                        ]);
                        $sheet->getStyle("{$col}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    $sheet->getRowDimension($totalRow)->setRowHeight(18);
                }
            },
        ];
    }
}
