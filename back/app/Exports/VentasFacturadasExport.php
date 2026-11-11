<?php

namespace App\Exports;

use App\Models\Venta;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ventas facturadas: las marcadas "Con factura", con su número, para conciliar
 * con el talonario o el sistema de facturación. Las anuladas se listan (su
 * número de factura debe anularse también) pero no suman en el total.
 */
class VentasFacturadasExport implements FromArray, WithColumnWidths, WithStyles, WithTitle
{
    /** Fila del encabezado de la tabla: antes van el título, el rango y el resumen. */
    private const FILA_ENCABEZADO = 5;

    public function __construct(
        protected Collection $ventas,
        protected bool $verMontos,
        protected string $rango,
    ) {}

    private function columnas(): array
    {
        $cols = ['N° venta', 'Fecha y hora', 'N° factura', 'Cliente / Razón social', 'CI / NIT', 'Tipo paciente',
            'Tipo padre', 'Detalle', 'Seguro', 'Vendedor', 'Estado', 'Forma de pago'];
        if ($this->verMontos) {
            array_push($cols, 'Efectivo (Bs)', 'QR (Bs)', 'Total (Bs)');
        }

        return $cols;
    }

    private static function vigente(Venta $venta): bool
    {
        return $venta->estado !== 'ANULADO';
    }

    public function array(): array
    {
        $n = count($this->columnas());
        $vigentes = $this->ventas->filter(fn (Venta $v) => self::vigente($v));

        $resumen = "Facturas: {$this->ventas->count()} · Vigentes: {$vigentes->count()} · Anuladas: ".($this->ventas->count() - $vigentes->count());
        if ($this->verMontos) {
            $resumen .= ' · Total facturado: Bs '.number_format((float) $vigentes->sum('total'), 2);
        }

        $filas = [
            array_pad(['CLÍNICA URME · REPORTE DE VENTAS FACTURADAS'], $n, ''),
            array_pad([$this->rango.' · Generado: '.now()->format('d/m/Y H:i')], $n, ''),
            array_pad([$resumen], $n, ''),
            array_fill(0, $n, ''),
            $this->columnas(),
        ];

        foreach ($this->ventas as $venta) {
            $fila = [
                $venta->id,
                optional($venta->fecha_hora)->format('d/m/Y H:i'),
                (string) ($venta->numero_factura ?: '—'),
                VentasExport::cliente($venta),
                (string) ($venta->paciente?->ci ?: ''),
                (string) ($venta->tipo_paciente ?: ''),
                $venta->tiposPadreTexto(),
                $venta->detalles->map(fn ($d) => ((float) $d->cantidad != 1 ? rtrim(rtrim(number_format((float) $d->cantidad, 2, '.', ''), '0'), '.').' × ' : '').$d->nombre)->implode(', '),
                $venta->seguro?->nombre ?: 'PARTICULAR',
                $venta->user?->name ?: '',
                VentasExport::estado($venta),
                $venta->tipo_pago ?: '',
            ];
            if ($this->verMontos) {
                array_push($fila, (float) $venta->monto_efectivo, (float) $venta->monto_qr, (float) $venta->total);
            }
            $filas[] = $fila;
        }

        if ($this->verMontos && $this->ventas->isNotEmpty()) {
            $filas[] = array_merge(array_fill(0, $n - 4, ''), [
                'TOTAL FACTURADO (sin anuladas)',
                (float) $vigentes->sum('monto_efectivo'),
                (float) $vigentes->sum('monto_qr'),
                (float) $vigentes->sum('total'),
            ]);
        }

        if ($this->ventas->isEmpty()) {
            $filas[] = array_pad(['No hay ventas facturadas con los filtros seleccionados.'], $n, '');
        }

        return $filas;
    }

    public function columnWidths(): array
    {
        $anchos = [10, 16, 14, 34, 14, 14, 22, 48, 18, 20, 12, 14, 14, 14, 14];

        return collect($anchos)->take(count($this->columnas()))
            ->mapWithKeys(fn ($ancho, $i) => [Coordinate::stringFromColumnIndex($i + 1) => $ancho])
            ->all();
    }

    public function title(): string
    {
        return 'Facturadas';
    }

    public function styles(Worksheet $sheet): array
    {
        $col = Coordinate::stringFromColumnIndex(count($this->columnas()));
        $h = self::FILA_ENCABEZADO;
        $primera = $h + 1;
        $last = $sheet->getHighestRow();
        $hayTotal = $this->verMontos && $this->ventas->isNotEmpty();
        $ultimaDato = $hayTotal ? $last - 1 : $last;

        // Título, rango y resumen
        foreach ([1, 2, 3] as $r) {
            $sheet->mergeCells("A{$r}:{$col}{$r}");
        }
        $sheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1565C0']]]);
        $sheet->getStyle('A2')->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '475569']]]);
        $sheet->getStyle('A3')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '172033']]]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Encabezado de la tabla
        $sheet->getStyle("A{$h}:{$col}{$h}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension($h)->setRowHeight(22);
        $sheet->freezePane('A'.$primera);

        if ($this->ventas->isEmpty()) {
            $sheet->mergeCells("A{$primera}:{$col}{$primera}");
            $sheet->getStyle("A{$primera}")->getFont()->setItalic(true)->getColor()->setRGB('64748B');

            return [];
        }

        $sheet->setAutoFilter("A{$h}:{$col}{$ultimaDato}");

        $fila = $primera;
        foreach ($this->ventas as $venta) {
            $sheet->getStyle("A{$fila}:{$col}{$fila}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fila % 2 === 0 ? 'E3F2FD' : 'FFFFFF']],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
            if (! self::vigente($venta)) {
                // Anulada: en rojo y tachada, para anular también la factura.
                $sheet->getStyle("A{$fila}:{$col}{$fila}")->getFont()->setStrikethrough(true)->getColor()->setRGB('C62828');
            }
            $fila++;
        }

        // N° de factura y N° de venta destacados y centrados; detalle en varias líneas.
        $sheet->getStyle("C{$primera}:C{$ultimaDato}")->getFont()->setBold(true);
        $sheet->getStyle("A{$primera}:C{$ultimaDato}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("K{$primera}:L{$ultimaDato}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$primera}:H{$ultimaDato}")->getAlignment()->setWrapText(true);
        // El N° de factura y el CI se guardan como texto: no pierden ceros a la izquierda.
        $sheet->getStyle("C{$primera}:C{$ultimaDato}")->getNumberFormat()->setFormatCode('@');
        $sheet->getStyle("E{$primera}:E{$ultimaDato}")->getNumberFormat()->setFormatCode('@');

        if ($this->verMontos) {
            $sheet->getStyle("M{$primera}:O{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("M{$primera}:O{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("O{$primera}:O{$ultimaDato}")->getFont()->setBold(true);
        }

        if ($hayTotal) {
            $sheet->getStyle("A{$last}:{$col}{$last}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '0D47A1']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BBDEFB']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1565C0']]],
            ]);
            $sheet->getStyle("L{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($h, $h);

        return [];
    }
}
