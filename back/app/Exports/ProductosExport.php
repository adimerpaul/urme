<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProductosExport implements FromCollection, WithHeadings, WithStyles, WithTitle, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Producto::with(['fabricante:id,nombre', 'unidad:id,nombre,abreviatura'])
            ->orderBy('nombre');

        if (!empty($this->filters['q'])) {
            $q = $this->filters['q'];
            $query->where(function ($sq) use ($q) {
                $sq->where('nombre',  'like', "%$q%")
                   ->orWhere('codigo', 'like', "%$q%")
                   ->orWhere('marca',  'like', "%$q%");
            });
        }

        $tipo = $this->filters['tipo'] ?? 'FARMACIA';
        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        return $query->get()->map(fn($p) => [
            $p->codigo                                                 ?? '',
            $p->nombre                                                 ?? '',
            $p->marca                                                  ?? '',
            $p->descripcion                                            ?? '',
            $p->fabricante?->nombre                                    ?? '',
            $p->unidad ? ($p->unidad->abreviatura ?: $p->unidad->nombre) : '',
            $p->tipo                                                   ?? '',
        ]);
    }

    public function headings(): array
    {
        return ['Código', 'Nombre', 'Marca', 'Descripción', 'Fabricante', 'Unidad', 'Tipo'];
    }

    public function title(): string
    {
        return 'Productos';
    }

    public function styles(Worksheet $sheet): array
    {
        $last = $sheet->getHighestRow();

        $sheet->getStyle('A1:G1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00695C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(20);

        for ($row = 2; $row <= $last; $row++) {
            $color = ($row % 2 === 0) ? 'E0F2F1' : 'FFFFFF';
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'CCCCCC']]],
            ]);
        }

        return [];
    }
}
