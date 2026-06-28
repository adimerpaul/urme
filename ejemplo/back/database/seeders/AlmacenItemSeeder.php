<?php

namespace Database\Seeders;

use App\Models\AlmacenItem;
use App\Models\Subpartida;
use Illuminate\Database\Seeder;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class AlmacenItemSeeder extends Seeder
{
    private array $files = [
        '25600' => 'almacenen 2026 clasificador_25600.xlsx',
        '31140' => 'almacenen 2026 clasificador_31140.xlsx',
        '32100' => 'almacenen 2026 clasificador_32100.xlsx',
        '32200' => 'almacenen 2026 clasificador_32200.xlsx',
        '32300' => 'almacenen 2026 clasificador_32300.xlsx',
        '33100' => 'almacenen 2026 clasificador_33100.xlsx',
        '33200' => 'almacenen 2026 clasificador_33200.xlsx',
        '33300' => 'almacenen 2026 clasificador_33300.xlsx',
        '33400' => 'almacenen 2026 clasificador_33400.xlsx',
        '34100' => 'almacenen 2026 clasificador_34100.xlsx',
        '34200' => 'almacenen 2026 clasificador_34200.xlsx',
        '34300' => 'almacenen 2026 clasificador_34300.xlsx',
        '34400' => 'almacenen 2026 clasificador_34400.xlsx',
        '34500' => 'almacenen 2026 clasificador_34500.xlsx',
        '34600' => 'almacenen 2026 clasificador_34600.xlsx',
        '34800' => 'almacenen 2026 clasificador_34800.xlsx',
        '39100' => 'almacenen 2026 clasificador_39100.xlsx',
        '39300' => 'almacenen 2026 clasificador_39300.xlsx',
        '39400' => 'almacenen 2026 clasificador_39400.xlsx',
        '39500' => 'almacenen 2026 clasificador_39500.xlsx',
        '39700' => 'almacenen 2026 clasificador_39700.xlsx',
        '39800' => 'almacenen 2026 clasificador_39800.xlsx',
    ];

    public function run(): void
    {
        $this->call(ClasificadorPresupuestarioSeeder::class);

        $basePath = env(
            'ALMACEN_EXCEL_PATH',
            database_path('seeders/data/almacen')
        );

        foreach ($this->files as $codigoSubpartida => $fileName) {
            $path = $basePath . DIRECTORY_SEPARATOR . $fileName;

            if (! is_file($path)) {
                throw new RuntimeException("No se encontró el archivo de almacén: {$path}");
            }

            $subpartida = Subpartida::where('codigo', $codigoSubpartida)->firstOrFail();

            foreach ($this->readRows($path) as $row) {
                AlmacenItem::updateOrCreate(
                    [
                        'subpartida_id' => $subpartida->id,
                        'nombre' => $row['nombre'],
                        'unidad_medida' => $row['unidad_medida'],
                    ],
                    [
                        'precio_unitario' => $row['precio_unitario'],
                    ]
                );
            }
        }
    }

    private function readRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException("No se pudo abrir el Excel: {$path}");
        }

        try {
            $sharedStrings = $this->readSharedStrings($zip);
            $sheetXml = $zip->getFromName($this->firstSheetPath($zip));

            if ($sheetXml === false) {
                throw new RuntimeException("No se pudo leer la hoja principal: {$path}");
            }

            $sheet = new SimpleXMLElement($sheetXml);
            $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $rows = [];
            $headerFound = false;

            foreach ($sheet->xpath('//m:sheetData/m:row') as $row) {
                $cells = $this->rowCells($row, $sharedStrings);

                if (! $headerFound) {
                    $headerFound = $this->isHeaderRow($cells);
                    continue;
                }

                $nombre = $this->cleanText($cells['B'] ?? null);
                $unidadMedida = $this->cleanText($cells['C'] ?? null);
                $precioUnitario = $this->decimalValue($cells['D'] ?? null);

                if ($nombre === null || $unidadMedida === null || $precioUnitario === null) {
                    continue;
                }

                $rows[] = [
                    'nombre' => $nombre,
                    'unidad_medida' => $unidadMedida,
                    'precio_unitario' => $precioUnitario,
                ];
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $sharedStrings = [];
        $shared = new SimpleXMLElement($xml);
        $shared->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        foreach ($shared->xpath('//m:si') as $item) {
            $sharedStrings[] = $this->textFromNode($item);
        }

        return $sharedStrings;
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                return $name;
            }
        }

        throw new RuntimeException('El Excel no contiene hojas.');
    }

    private function rowCells(SimpleXMLElement $row, array $sharedStrings): array
    {
        $cells = [];
        $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        foreach ($row->xpath('m:c') as $cell) {
            $cell->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $reference = (string) $cell['r'];
            $column = preg_replace('/\d+/', '', $reference);
            $type = (string) $cell['t'];
            $value = $this->cellRawValue($cell);

            if ($type === 's') {
                $value = $sharedStrings[(int) $value] ?? null;
            } elseif ($type === 'inlineStr') {
                $value = $this->textFromNode($cell);
            }

            if ($column !== '') {
                $cells[$column] = $value;
            }
        }

        return $cells;
    }

    private function cellRawValue(SimpleXMLElement $cell): ?string
    {
        $value = $cell->xpath('m:v');

        if ($value && isset($value[0])) {
            return (string) $value[0];
        }

        return null;
    }

    private function textFromNode(SimpleXMLElement $node): string
    {
        $node->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $texts = [];

        foreach ($node->xpath('.//m:t') as $text) {
            $texts[] = (string) $text;
        }

        return implode('', $texts);
    }

    private function isHeaderRow(array $cells): bool
    {
        return strtoupper($this->cleanText($cells['B'] ?? '') ?? '') === 'ITEM'
            && strtoupper($this->cleanText($cells['C'] ?? '') ?? '') === 'UNIDAD MEDIDA';
    }

    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }

    private function decimalValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '.', (string) $value);
        $value = preg_replace('/[^0-9.\-]/', '', $value);

        return is_numeric($value) ? number_format((float) $value, 4, '.', '') : null;
    }
}
