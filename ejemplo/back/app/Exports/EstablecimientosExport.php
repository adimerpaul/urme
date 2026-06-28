<?php

namespace App\Exports;

use App\Models\Establecimiento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class EstablecimientosExport implements
    FromCollection,
    WithHeadings,
    WithStyles,
    WithTitle,
    ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Establecimiento::orderBy('nombre', 'asc');

        if (!empty($this->filters['tipos'])) {
            $tipos = array_filter(explode(',', $this->filters['tipos']));
            if (count($tipos)) {
                $query->where(function ($q) use ($tipos) {
                    if (in_array('PUBLICO', $tipos))  $q->orWhere('es_publico', true);
                    if (in_array('URBANO', $tipos))   $q->orWhere('es_lab_urbano', true);
                    if (in_array('RURAL', $tipos))    $q->orWhere('es_lab_rural', true);
                    if (in_array('PRIVADO', $tipos))  $q->orWhere('es_privado', true);
                });
            }
        }
        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }
        if (!empty($this->filters['q'])) {
            $q = $this->filters['q'];
            $query->where(function ($qq) use ($q) {
                $qq->where('nombre', 'like', "%{$q}%")
                    ->orWhere('direccion', 'like', "%{$q}%")
                    ->orWhere('responsable_laboratorio', 'like', "%{$q}%");
            });
        }

        return $query->get()->map(function ($e) {
            $tipos = array_filter([
                $e->es_publico    ? 'Público'            : null,
                $e->es_lab_urbano ? 'Laboratorio Urbano' : null,
                $e->es_lab_rural  ? 'Laboratorio Rural'  : null,
                $e->es_privado    ? 'Privado'            : null,
            ]);
            return [
                $e->nombre                    ?? '',
                implode(', ', $tipos),
                $e->nivel                     ?? '',
                $e->direccion                 ?? '',
                $e->telefono_contacto         ?? '',
                $e->responsable_laboratorio   ?? '',
                $e->telefono_responsable      ?? '',
                $e->inicial                   ?? '',
                $e->estado                    ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nombre', 'Tipo', 'Nivel', 'Dirección',
            'Tel. Contacto', 'Responsable Laboratorio',
            'Tel. Responsable', 'Iniciales', 'Estado',
        ];
    }

    public function title(): string
    {
        return 'Establecimientos';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();
        $lastCol = 'I';

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $lastRow; $row++) {
            $color = ($row % 2 === 0) ? 'E0F2F1' : 'FFFFFF';
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        // Tipo: verde = PUBLICO, morado = PRIVADO
        for ($row = 2; $row <= $lastRow; $row++) {
            $tipo  = $sheet->getCell("B{$row}")->getValue();
            $color = $tipo === 'PUBLICO' ? '2E7D32' : '4527A0';
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB($color);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        }

        // Estado
        for ($row = 2; $row <= $lastRow; $row++) {
            $estado = $sheet->getCell("I{$row}")->getValue();
            $color  = $estado === 'ACTIVO' ? '1B5E20' : '616161';
            $sheet->getStyle("I{$row}")->getFont()->getColor()->setRGB($color);
            $sheet->getStyle("I{$row}")->getFont()->setBold(true);
        }

        return [];
    }
}
