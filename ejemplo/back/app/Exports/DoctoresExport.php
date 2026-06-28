<?php

namespace App\Exports;

use App\Models\Doctor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DoctoresExport implements
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
        $query = Doctor::with('establecimiento')->orderBy('nombre', 'asc');

        if (!empty($this->filters['estado'])) {
            $query->where('estado', $this->filters['estado']);
        }

        return $query->get()->map(fn($d) => [
            $d->nombre          ?? '',
            $d->especialidad    ?? '',
            $d->ci              ?? '',
            $d->telefono        ?? '',
            $d->email           ?? '',
            $d->registro        ?? '',
            $d->establecimiento?->nombre ?? '',
            $d->estado          ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['Nombre', 'Especialidad', 'CI', 'Teléfono', 'Email', 'Registro Profesional', 'Establecimiento', 'Estado'];
    }

    public function title(): string
    {
        return 'Doctores';
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $sheet->getHighestRow();

        // Cabecera
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        // Filas alternas
        for ($row = 2; $row <= $lastRow; $row++) {
            $color = ($row % 2 === 0) ? 'EAF2FF' : 'FFFFFF';
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        // Colorear columna Estado (H)
        for ($row = 2; $row <= $lastRow; $row++) {
            $estado = $sheet->getCell("H{$row}")->getValue();
            $color  = $estado === 'ACTIVO' ? '1B5E20' : '616161';
            $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB($color);
            $sheet->getStyle("H{$row}")->getFont()->setBold(true);
        }

        return [];
    }
}
