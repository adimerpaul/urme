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

class ReporteValoradoExport implements FromArray, WithTitle, WithColumnWidths, WithStyles, WithEvents
{
    private array $productRows = []; // tracks [rowIndex => 'product'|'header'|'data'|'total'|'summary']
    private int $currentRow = 0;

    public function __construct(
        private array  $cards,
        private ?string $dateFrom,
        private ?string $dateTo,
    ) {}

    public function array(): array
    {
        $periodo = $this->dateFrom && $this->dateTo
            ? 'Del ' . \Carbon\Carbon::parse($this->dateFrom)->format('d/m/Y')
              . ' al ' . \Carbon\Carbon::parse($this->dateTo)->format('d/m/Y')
            : 'Todos los períodos';

        $data = [];

        // Header block (rows 1-6)
        $data[] = ['REPORTE VALORADO - PEPS', '', '', '', '', '', '', '', '', '', 'Versión 01'];
        $data[] = ['HOSPITAL GENERAL SAN JUAN DE DIOS ORURO'];
        $data[] = ['REPORTE TOTAL VALORADO (MÉTODO PEPS — PRIMERAS ENTRADAS, PRIMERAS SALIDAS)'];
        $data[] = [strtoupper($periodo)];
        $data[] = ['(Expresado en Bolivianos)'];
        $data[] = [];

        $this->currentRow = 6;

        foreach ($this->cards as $card) {
            // Product header row
            $this->currentRow++;
            $this->productRows[$this->currentRow] = 'product';
            $data[] = [$card['producto'] . '   [' . $card['unidad'] . ']', '', '', '', '', '', '', '', '', '', ''];

            // Column headers row
            $this->currentRow++;
            $this->productRows[$this->currentRow] = 'header';
            $data[] = [
                'Fecha', 'Concepto',
                'Cant. Entrada', 'V. Unit. Entrada', 'V. Total Entrada',
                'Cant. Salida',  'V. Unit. Salida',  'V. Total Salida',
                'Saldo Cant.',   'V. Unit. Saldo',   'V. Total Saldo',
            ];

            // Data rows
            foreach ($card['rows'] as $row) {
                $this->currentRow++;
                $this->productRows[$this->currentRow] = $row['tipo'];
                if ($row['tipo'] === 'ENTRADA') {
                    $data[] = [
                        \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y'),
                        $row['concepto'],
                        $row['cantidad'],         $row['precio_unitario'],  $row['total'],
                        '',                       '',                       '',
                        $row['saldo_cantidad'],   $row['saldo_precio_unitario'], $row['saldo_total'],
                    ];
                } else {
                    $data[] = [
                        \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y'),
                        $row['concepto'],
                        '',                       '',                       '',
                        $row['cantidad'],         $row['precio_unitario'],  $row['total'],
                        $row['saldo_cantidad'],   $row['saldo_precio_unitario'], $row['saldo_total'],
                    ];
                }
            }

            // Totals row
            $s = $card['summary'];
            $this->currentRow++;
            $this->productRows[$this->currentRow] = 'total';
            $data[] = [
                'TOTALES', '',
                $s['total_entradas_cantidad'], '', $s['total_entradas_valor'],
                $s['total_salidas_cantidad'],  '', $s['total_salidas_valor'],
                $s['saldo_final_cantidad'],    '', $s['saldo_final_valor'],
            ];

            // Empty separator
            $this->currentRow++;
            $data[] = [];
        }

        return $data;
    }

    public function title(): string { return 'Reporte PEPS'; }

    public function columnWidths(): array
    {
        return [
            'A' => 13, 'B' => 32,
            'C' => 14, 'D' => 16, 'E' => 16,
            'F' => 14, 'G' => 16, 'H' => 16,
            'I' => 13, 'J' => 15, 'K' => 16,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['size' => 9]],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['bold' => true, 'size' => 10], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            4 => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            5 => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge header rows
                foreach (['A2:K2', 'A3:K3', 'A4:K4', 'A5:K5'] as $range) {
                    $sheet->mergeCells($range);
                }

                $numFmt = '#,##0.00';

                foreach ($this->productRows as $r => $type) {
                    match ($type) {
                        'product' => $this->styleProductRow($sheet, $r),
                        'header'  => $this->styleHeaderRow($sheet, $r),
                        'ENTRADA' => $this->styleDataRow($sheet, $r, 'entrada'),
                        'SALIDA'  => $this->styleDataRow($sheet, $r, 'salida'),
                        'total'   => $this->styleTotalRow($sheet, $r),
                        default   => null,
                    };

                    if (in_array($type, ['ENTRADA', 'SALIDA', 'total'])) {
                        // Number format for value columns
                        foreach (['D', 'E', 'G', 'H', 'J', 'K'] as $col) {
                            $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode($numFmt);
                        }
                    }
                }
            },
        ];
    }

    private function styleProductRow(Worksheet $sheet, int $r): void
    {
        $sheet->mergeCells("A{$r}:K{$r}");
        $sheet->getStyle("A{$r}:K{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0A3A8A']]],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(18);
    }

    private function styleHeaderRow(Worksheet $sheet, int $r): void
    {
        $sheet->getStyle("A{$r}:B{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0D47A1']]],
        ]);
        $sheet->getStyle("C{$r}:E{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '145219']]],
        ]);
        $sheet->getStyle("F{$r}:H{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B71C1C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '951818']]],
        ]);
        $sheet->getStyle("I{$r}:K{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 8, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0A3A8A']]],
        ]);
        $sheet->getRowDimension($r)->setRowHeight(28);
    }

    private function styleDataRow(Worksheet $sheet, int $r, string $tipo): void
    {
        $bg = $tipo === 'entrada' ? 'F1F8E9' : 'FFF3E0';
        $sheet->getStyle("A{$r}:K{$r}")->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
            'font'    => ['size' => 8],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C5D9EF']]],
        ]);
        $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    private function styleTotalRow(Worksheet $sheet, int $r): void
    {
        $sheet->getStyle("A{$r}:K{$r}")->applyFromArray([
            'font'    => ['bold' => true, 'size' => 8, 'color' => ['rgb' => '0D47A1']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1565C0']],
                          'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C5D9EF']]],
        ]);
    }
}
