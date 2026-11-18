<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Todos los movimientos (ventas y gastos) que componen un cierre de caja, con el resumen de caja al final. */
class CierreCajaVentasExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    /** Filas de datos (sin el encabezado): el resumen de caja empieza después. */
    private int $filasMovimientos = 0;

    /**
     * @param  Builder  $ventas  Movimientos del cierre (su día o el rango filtrado).
     * @param  array|null  $resumenCaja  CierreCajaController::resumenCaja(); importes null sin 'Ver Montos Caja'.
     * @param  bool  $verMontos  Sin 'Ver Montos Caja' la columna Total y los precios no se exportan.
     */
    public function __construct(protected Builder $ventas, protected ?array $resumenCaja = null, protected bool $verMontos = true) {}

    public function collection()
    {
        $filas = $this->ventas
            ->with(['paciente:id,nombre_completo,ci', 'detalles:id,venta_id,nombre,cantidad,precio,precio_original,total'])
            ->orderBy('fecha_hora')
            ->get()
            ->map(fn ($venta) => [
                $venta->id,
                optional($venta->fecha_hora_cobro ?: $venta->fecha_hora)->format('d/m/Y H:i'),
                $venta->paciente?->nombre_completo ?: ($venta->cliente ?: ($venta->esEgreso() ? 'GASTO DE CAJA' : 'SIN CLIENTE')),
                $venta->paciente?->ci ?: '',
                $venta->esEgreso() ? 'GASTO' : 'VENTA',
                $venta->estado,
                $venta->tipo_pago ?: '',
                $venta->detalles->count(),
                // "*" marca el precio cambiado por la vendedora respecto al de lista.
                $venta->detalles->map(fn ($d) => (float) $d->cantidad.' x '.$d->nombre.($this->verMontos ? ' @ '
                    .number_format((float) $d->precio, 2).($d->precio_modificado ? '* (lista '.number_format((float) $d->precio_original, 2).')' : '') : ''))
                    ->implode(', '),
                $venta->comentario ?: '',
                // El gasto va en negativo: así la columna suma el neto que queda en caja.
                $this->verMontos ? ($venta->esEgreso() ? -(float) $venta->total : (float) $venta->total) : '—',
            ]);
        $this->filasMovimientos = $filas->count();

        if (! $this->resumenCaja) {
            return $filas;
        }

        // Resumen de caja: etiqueta en la columna A, cantidad en J e importe (Bs) en K.
        $r = $this->resumenCaja;
        $fila = fn (string $etiqueta, $cantidad, $importe) => [$etiqueta, '', '', '', '', '', '', '', '', $cantidad, $importe ?? '—'];
        $filas->push(array_fill(0, 11, ''));
        $filas->push($fila('RESUMEN DE CAJA', 'Cantidad', 'Importe (Bs)'));
        $filas->push($fila('Ventas anuladas (no se suman)', $r['anuladas']['cantidad'], $r['anuladas']['importe']));
        $filas->push($fila('Total ventas', $r['total_ventas']['cantidad'], $r['total_ventas']['importe']));
        $filas->push($fila('(−) Ventas pendientes de cobro', $r['pendientes']['cantidad'], $r['pendientes']['importe']));
        $filas->push($fila('(−) Cobros por QR', $r['qr']['cantidad'], $r['qr']['importe']));
        $filas->push($fila('(−) Otros medios (transferencia / tarjeta)', $r['otros']['cantidad'], $r['otros']['importe']));
        $filas->push($fila('(−) Gastos en efectivo', $r['gastos']['cantidad'], $r['gastos']['importe']));
        $filas->push($fila('TOTAL EFECTIVO', '', $r['total_efectivo']));

        return $filas;
    }

    public function headings(): array
    {
        return ['N°', 'Fecha y hora', 'Cliente / Paciente', 'CI', 'Tipo', 'Estado', 'Pago', 'Ítems', 'Detalle', 'Comentario', 'Total (Bs)'];
    }

    public function title(): string
    {
        return 'Movimientos del cierre';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();
        // Fila 1: encabezado; luego los movimientos; luego una fila vacía y el resumen.
        $ultimaMovimiento = 1 + $this->filasMovimientos;

        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $ultimaMovimiento; $row++) {
            $color = ($row % 2 === 0) ? 'E0F2F1' : 'FFFFFF';
            $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        if ($this->resumenCaja) {
            $titulo = $ultimaMovimiento + 2;
            $total = $titulo + 7;
            for ($row = $titulo; $row <= $last; $row++) {
                $sheet->mergeCells("A{$row}:I{$row}");
            }
            $sheet->getStyle("A{$titulo}:K{$titulo}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            ]);
            $sheet->getStyle('A'.($titulo + 1).':K'.($titulo + 1))->getFont()->setItalic(true)->getColor()->setRGB('64748B');
            $sheet->getStyle("A{$titulo}:K{$total}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR);
            $sheet->getStyle("A{$total}:K{$total}")->getFont()->setBold(true);
            $sheet->getStyle("J{$titulo}:K{$total}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('K'.($titulo + 1).":K{$total}")->getNumberFormat()->setFormatCode('#,##0.00');
        }

        return [];
    }
}
