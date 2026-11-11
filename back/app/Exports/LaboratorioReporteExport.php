<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaboratorioReporteExport extends StringValueBinder implements FromCollection, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    /** @param  Collection  $items  Filas de LaboratorioReporteController (exámenes y perfiles). */
    public function __construct(private Collection $items) {}

    public function collection()
    {
        return $this->items;
    }

    public function headings(): array
    {
        return ['Fecha de solicitud', 'Solicitud', 'Código laboratorio', 'Paciente', 'CI', 'Laboratorio / prueba', 'Estado', 'Importe (Bs)'];
    }

    public function map($item): array
    {
        return [
            $item->solicitude->fecha_solicitud->format('d/m/Y'),
            $item->solicitude->codigo_completo,
            $item->solicitude->codigo_laboratorio,
            $item->solicitude->paciente?->nombre_completo,
            $item->solicitude->paciente?->ci,
            $item->clase === 'perfil' ? $item->producto_nombre.' (PERFIL)' : $item->producto_nombre,
            $item->solicitude->estado,
            // Un examen de perfil no tiene importe propio: lo cobra el perfil.
            $item->perfil_nombre ? 'Incluido en '.$item->perfil_nombre : (float) $item->precio,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '00695C']]]];
    }
}
