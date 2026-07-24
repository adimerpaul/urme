<?php

namespace App\Http\Controllers;

use App\Models\AlmacenItem;
use App\Models\Compra;
use App\Models\Unidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with([
            'proveedor:id,nombre,carnet',
            'user:id,name',
            'detalles:id,compra_id,nombre,cantidad',
        ])
            ->withCount('detalles')
            ->withSum('detalles as vendido_total', 'cantidad_venta')
            ->withCount(['despachoDetalleReales as despachado_count' => function ($q) {
                $q->whereHas('despacho', fn ($d) => $d->where('estado', '!=', 'ANULADO'));
            }]);

        $this->applyFilters($query, $request);

        $rowsPerPage = (int) $request->input('rowsPerPage', 15);
        $rowsPerPage = $rowsPerPage > 0 ? $rowsPerPage : 15;

        $summaryQuery = Compra::query();
        $this->applyFilters($summaryQuery, $request);
        $summary = [
            'total_compras' => (float) (clone $summaryQuery)->where('estado', 'ACTIVO')->sum('total'),
            'total_anuladas' => (float) (clone $summaryQuery)->where('estado', 'ANULADO')->sum('total'),
            'cantidad' => (int) (clone $summaryQuery)->count(),
        ];

        $paginated = $query
            ->orderBy('fecha_hora', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($rowsPerPage);

        $response = $paginated->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    public function reportExcel(Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $query = Compra::with(['proveedor:id,nombre', 'user:id,name'])
            ->withCount('detalles');
        $this->applyFilters($query, $request);

        $compras = $query->orderBy('fecha_hora', 'asc')->orderBy('id', 'asc')->get();

        // ── Paleta ─────────────────────────────────────────────────────
        $cAzul     = 'FF1A237E';
        $cAzulMed  = 'FF283593';
        $cAzulCla  = 'FFE8EAF6';
        $cGrisCab  = 'FF455A64';
        $cVerde    = 'FF1B5E20';
        $cRojo     = 'FFB71C1C';
        $cBlanco   = 'FFFFFFFF';
        $cGrisFila = 'FFF5F5F5';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Compras');

        // A=Nro B=Fecha C=Proveedor D=Factura E=Motivo F=Pago G=Items H=Estado I=Total
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(34);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(16);

        // ── Fila 1: Título ─────────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'LABORATORIO CLÍNICO SIL — REPORTE DE COMPRAS');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => $cBlanco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzul]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Fila 2: Meta info ──────────────────────────────────────────
        $rango = 'Todas las fechas';
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $rango = ($request->input('date_from', '...')).' al '.($request->input('date_to', '...'));
        }
        $sheet->mergeCells('A2:I2');
        $metaInfo = 'Generado: '.now()->format('d/m/Y H:i')
            .'   |   Periodo: '.$rango
            .'   |   Estado: '.($request->input('estado') ?: 'Todos');
        if ($request->filled('q')) {
            $metaInfo .= '   |   Búsqueda: '.$request->q;
        }
        $sheet->setCellValue('A2', $metaInfo);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF333333']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzulCla]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(14);

        // ── Fila 3: Cabeceras ──────────────────────────────────────────
        $headerRow = 3;
        $headers = ['A' => 'Nro', 'B' => 'Fecha', 'C' => 'Proveedor', 'D' => 'N° Factura',
                    'E' => 'Motivo', 'F' => 'Tipo Pago', 'G' => 'Items', 'H' => 'Estado', 'I' => 'Total (Bs)'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }
        $sheet->getStyle("A{$headerRow}:I{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $cBlanco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzulMed]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF90A4AE']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(18);
        $sheet->setAutoFilter("A{$headerRow}:I{$headerRow}");
        $sheet->freezePane('A'.($headerRow + 1));

        // ── Filas de datos ─────────────────────────────────────────────
        $row = $headerRow + 1;
        $firstDataRow = $row;

        foreach ($compras as $i => $compra) {
            $estado = $compra->estado;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValueExplicit("B{$row}", $compra->fecha_hora ? \Carbon\Carbon::parse($compra->fecha_hora)->format('d/m/Y H:i') : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $compra->proveedor->nombre ?? $compra->nombre ?? 'Sin proveedor');
            $sheet->setCellValueExplicit("D{$row}", (string) ($compra->nro_factura ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("E{$row}", ucfirst(strtolower($compra->motivo_registro ?? '')));
            $sheet->setCellValue("F{$row}", ucfirst(strtolower($compra->tipo_pago ?? '')));
            $sheet->setCellValue("G{$row}", (int) $compra->detalles_count);
            $sheet->setCellValue("H{$row}", $estado);
            $sheet->setCellValue("I{$row}", (float) $compra->total);

            $bg = $i % 2 === 0 ? $cBlanco : $cGrisFila;
            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
                'font'    => ['size' => 9],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
            $sheet->getStyle("H{$row}")->getFont()->setBold(true)->getColor()->setARGB($estado === 'ACTIVO' ? $cVerde : $cRojo);
            $sheet->getRowDimension($row)->setRowHeight(14);
            $row++;
        }

        $lastDataRow = $row - 1;
        if ($compras->isEmpty()) {
            // Sin filas: dejamos el rango en la cabecera para que las fórmulas no fallen
            $firstDataRow = $headerRow + 1;
            $lastDataRow = $headerRow + 1;
        }

        // ── Fila de totales con FÓRMULAS de Excel ──────────────────────
        $row++; // deja una fila en blanco
        $sumItems = "=SUM(G{$firstDataRow}:G{$lastDataRow})";
        $sumTotal = "=SUM(I{$firstDataRow}:I{$lastDataRow})";

        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL GENERAL');
        $sheet->setCellValue("G{$row}", $sumItems);
        $sheet->setCellValue("I{$row}", $sumTotal);
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => $cBlanco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzul]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF90A4AE']]],
        ]);
        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
        $sheet->getRowDimension($row)->setRowHeight(20);

        // Subtotal ACTIVO (SUMIF por estado)
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'Subtotal ACTIVO');
        $sheet->setCellValue("I{$row}", "=SUMIF(H{$firstDataRow}:H{$lastDataRow},\"ACTIVO\",I{$firstDataRow}:I{$lastDataRow})");
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $cVerde]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE8F5E9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

        // Subtotal ANULADO (SUMIF por estado)
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'Subtotal ANULADO');
        $sheet->setCellValue("I{$row}", "=SUMIF(H{$firstDataRow}:H{$lastDataRow},\"ANULADO\",I{$firstDataRow}:I{$lastDataRow})");
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $cRojo]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFEBEE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $sheet->getStyle("I{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

        // Cantidad de compras (COUNTA)
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'Cantidad de compras');
        $sheet->setCellValue("I{$row}", "=COUNTA(A{$firstDataRow}:A{$lastDataRow})");
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FF333333']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cGrisFila]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $filename = 'compras_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function printExcel($id)
    {
        @set_time_limit(120);

        $compra = Compra::with(['proveedor', 'unidad', 'user:id,name', 'detalles' => function ($q) {
            $q->orderBy('id');
        }])->findOrFail($id);

        // ── Paleta ─────────────────────────────────────────────────────
        $cAzul     = 'FF1A237E';
        $cAzulMed  = 'FF283593';
        $cAzulCla  = 'FFE8EAF6';
        $cVerde    = 'FF1B5E20';
        $cRojo     = 'FFB71C1C';
        $cBlanco   = 'FFFFFFFF';
        $cGrisFila = 'FFF5F5F5';
        $cGrisCab  = 'FF455A64';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Compra '.$compra->id);

        // A=Nro B=Producto C=Lote D=Vence E=Cantidad F=P.Unit G=Total
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(46);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(16);

        // ── Fila 1: Título ─────────────────────────────────────────────
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'LABORATORIO CLÍNICO SIL — COMPRA #'.$compra->id);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => $cBlanco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzul]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Filas 2-5: Datos de cabecera (pares etiqueta/valor) ────────
        $proveedor = $compra->proveedor->nombre ?? $compra->nombre ?? 'Sin proveedor';
        $fecha = $compra->fecha_hora ? \Carbon\Carbon::parse($compra->fecha_hora)->format('d/m/Y H:i') : '-';
        $meta = [
            ['Proveedor', $proveedor, 'N° Factura', $compra->nro_factura ?: '-'],
            ['Fecha', $fecha, 'Tipo de pago', ucfirst(strtolower($compra->tipo_pago ?? '-'))],
            ['Motivo', ucfirst(strtolower($compra->motivo_registro ?? '-')), 'Estado', $compra->estado],
            ['Registrado por', $compra->user->name ?? '-', 'Comentario', $compra->comentario ?: '-'],
        ];
        $r = 2;
        foreach ($meta as $par) {
            $sheet->setCellValue("A{$r}", $par[0]);
            $sheet->mergeCells("B{$r}:C{$r}");
            $sheet->setCellValue("B{$r}", $par[1]);
            $sheet->setCellValue("D{$r}", $par[2]);
            $sheet->mergeCells("E{$r}:G{$r}");
            $sheet->setCellValue("E{$r}", $par[3]);
            $sheet->getStyle("A{$r}")->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle("D{$r}")->getFont()->setBold(true)->setSize(9);
            $sheet->getStyle("B{$r}:C{$r}")->getFont()->setSize(9);
            $sheet->getStyle("E{$r}:G{$r}")->getFont()->setSize(9);
            $sheet->getStyle("A{$r}:G{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($cAzulCla);
            $sheet->getRowDimension($r)->setRowHeight(15);
            $r++;
        }
        // Estado coloreado
        $sheet->getStyle('E4')->getFont()->setBold(true)->getColor()->setARGB($compra->estado === 'ACTIVO' ? $cVerde : $cRojo);

        // ── Cabeceras de la tabla de productos ─────────────────────────
        $headerRow = $r + 1;
        $headers = ['A' => 'Nro', 'B' => 'Producto', 'C' => 'Lote', 'D' => 'Vence',
                    'E' => 'Cantidad', 'F' => 'P. Unit (Bs)', 'G' => 'Total (Bs)'];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $cBlanco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzulMed]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF90A4AE']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(18);

        // ── Filas de detalle ───────────────────────────────────────────
        $row = $headerRow + 1;
        $firstDataRow = $row;

        foreach ($compra->detalles as $i => $det) {
            $cantidad = (float) $det->cantidad;
            $precio   = (float) $det->precio;
            $vence    = $det->fecha_vencimiento ? \Carbon\Carbon::parse($det->fecha_vencimiento)->format('d/m/Y') : '';

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $det->nombre ?? '-');
            $sheet->setCellValueExplicit("C{$row}", (string) ($det->lote ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", $vence, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("E{$row}", $cantidad);
            $sheet->setCellValue("F{$row}", $precio);
            // Total como FÓRMULA: cantidad * precio unitario
            $sheet->setCellValue("G{$row}", "=E{$row}*F{$row}");

            $bg = $i % 2 === 0 ? $cBlanco : $cGrisFila;
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFDDDDDD']]],
                'font'    => ['size' => 9],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F{$row}:G{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
            $sheet->getRowDimension($row)->setRowHeight(14);
            $row++;
        }

        $lastDataRow = $row - 1;
        if ($compra->detalles->isEmpty()) {
            $firstDataRow = $headerRow + 1;
            $lastDataRow = $headerRow + 1;
        }

        // ── Fila de totales con FÓRMULAS ───────────────────────────────
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'TOTALES');
        $sheet->setCellValue("E{$row}", "=SUM(E{$firstDataRow}:E{$lastDataRow})");
        $sheet->setCellValue("G{$row}", "=SUM(G{$firstDataRow}:G{$lastDataRow})");
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['argb' => $cBlanco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $cAzul]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['argb' => 'FF90A4AE']]],
        ]);
        $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
        $sheet->getRowDimension($row)->setRowHeight(20);

        $filename = 'compra_'.$compra->id.'_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function show($id)
    {
        return Compra::with(['proveedor', 'unidad', 'user:id,name', 'detalles.producto'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero' => 'nullable|string|max:100',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'unidad_id' => 'nullable|exists:unidades,id',
            'fecha_hora' => 'nullable|date',
            'tipo_registro' => ['required', Rule::in(['ENTRADA', 'SALIDA'])],
            'motivo_registro' => 'required|string|max:50',
            'carnet' => 'nullable|string|max:100',
            'nombre' => 'nullable|string|max:255',
            'comentario' => 'nullable|string|max:500',
            'tipo_pago' => 'nullable|string|max:50',
            'nro_factura' => 'nullable|string|max:255',
            'categoria_programatica' => 'nullable|string|max:255',
            'orden_de_compra' => 'nullable|string|max:255',
            'metodo_orden' => ['nullable', Rule::in(['ORDEN DE COMPRA', 'ORDEN DE SERVICIO', 'CONTRATO'])],
            'fecha_orden' => 'nullable|date',
            'codigo_interno' => 'nullable|string|max:255',
            'hoja_de_ruta' => 'nullable|string',
            'retencion_porcentaje' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:almacen_items,id',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio' => 'nullable|numeric|min:0',
            'items.*.factor' => 'nullable|numeric|min:0',
            'items.*.precio_venta' => 'nullable|numeric|min:0',
            'items.*.lote' => 'nullable|string|max:255',
            'items.*.fecha_vencimiento' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $productos = AlmacenItem::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');
            $total = collect($data['items'])->sum(fn ($item) => (float) ($item['precio'] ?? 0) * (float) $item['cantidad']);

            $compra = Compra::create([
                'user_id' => $request->user()->id,
                'numero' => $data['numero'] ?? null,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'unidad_id' => $data['unidad_id'] ?? null,
                'fecha_hora' => $data['fecha_hora'] ?? now(),
                'tipo_registro' => $data['tipo_registro'],
                'motivo_registro' => strtoupper($data['motivo_registro']),
                'carnet' => $data['carnet'] ?? null,
                'nombre' => $data['nombre'] ?? null,
                'comentario' => $data['comentario'] ?? null,
                'estado' => 'ACTIVO',
                'total' => $total,
                'retencion_porcentaje' => $data['retencion_porcentaje'] ?? 0,
                'tipo_pago' => $data['tipo_pago'] ?? 'EFECTIVO',
                'nro_factura' => $data['nro_factura'] ?? null,
                'categoria_programatica' => $data['categoria_programatica'] ?? null,
                'orden_de_compra' => $data['orden_de_compra'] ?? null,
                'metodo_orden' => $data['metodo_orden'] ?? null,
                'fecha_orden' => $data['fecha_orden'] ?? null,
                'codigo_interno' => $data['codigo_interno'] ?? null,
                'hoja_de_ruta' => $data['hoja_de_ruta'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $producto = $productos[$item['producto_id']];
                $precio = (float) ($item['precio'] ?? $producto->precio_unitario ?? 0);
                $cantidad = (float) $item['cantidad'];
                $factor = (float) ($item['factor'] ?? 1.25);
                $precio13 = round($precio * $factor, 2);

                $compra->detalles()->create([
                    'user_id' => $request->user()->id,
                    'proveedor_id' => $data['proveedor_id'] ?? null,
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                    'cantidad_venta' => 0,
                    'total' => round($precio * $cantidad, 2),
                    'factor' => $factor,
                    'precio13' => $precio13,
                    'total13' => round($precio13 * $cantidad, 2),
                    'precio_venta' => $item['precio_venta'] ?? $precio13,
                    'estado' => 'ACTIVO',
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                    'nro_factura' => $data['nro_factura'] ?? null,
                ]);

                if ($data['tipo_registro'] === 'ENTRADA' && $precio > 0) {
                    $producto->update(['precio_unitario' => $precio]);
                }
            }

            return response()->json($compra->load(['proveedor', 'user:id,name', 'detalles.producto']), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $compra = Compra::with('detalles')->findOrFail($id);

        if ($compra->created_at->lt(now()->subMonth())) {
            return response()->json([
                'message' => 'No se puede modificar esta compra: tiene más de un mes de antigüedad.',
            ], 403);
        }

        $data = $request->validate([
            'numero' => 'nullable|string|max:100',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'unidad_id' => 'nullable|exists:unidades,id',
            'fecha_hora' => 'nullable|date',
            'tipo_registro' => ['required', Rule::in(['ENTRADA', 'SALIDA'])],
            'motivo_registro' => 'required|string|max:50',
            'carnet' => 'nullable|string|max:100',
            'nombre' => 'nullable|string|max:255',
            'comentario' => 'nullable|string|max:500',
            'tipo_pago' => 'nullable|string|max:50',
            'nro_factura' => 'nullable|string|max:255',
            'categoria_programatica' => 'nullable|string|max:255',
            'orden_de_compra' => 'nullable|string|max:255',
            'metodo_orden' => ['nullable', Rule::in(['ORDEN DE COMPRA', 'ORDEN DE SERVICIO', 'CONTRATO'])],
            'fecha_orden' => 'nullable|date',
            'codigo_interno' => 'nullable|string|max:255',
            'hoja_de_ruta' => 'nullable|string',
            'retencion_porcentaje' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:almacen_items,id',
            'items.*.cantidad' => 'required|numeric|min:0',
            'items.*.precio' => 'nullable|numeric|min:0',
            'items.*.factor' => 'nullable|numeric|min:0',
            'items.*.precio_venta' => 'nullable|numeric|min:0',
            'items.*.lote' => 'nullable|string|max:255',
            'items.*.fecha_vencimiento' => 'nullable|date',
        ]);

        return DB::transaction(function () use ($compra, $data, $request) {
            $productos = AlmacenItem::whereIn('id', collect($data['items'])->pluck('producto_id'))->get()->keyBy('id');
            $total = collect($data['items'])->sum(fn ($item) => (float) ($item['precio'] ?? 0) * (float) $item['cantidad']);

            // Preservar cantidad_venta existente por producto_id para no perder el tracking de despachos
            $cantidadesVenta = $compra->detalles->keyBy('producto_id')->map(fn ($d) => (float) $d->cantidad_venta);

            $compra->update([
                'numero' => $data['numero'] ?? null,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'unidad_id' => $data['unidad_id'] ?? null,
                'fecha_hora' => $data['fecha_hora'] ?? $compra->fecha_hora,
                'tipo_registro' => $data['tipo_registro'],
                'motivo_registro' => strtoupper($data['motivo_registro']),
                'carnet' => $data['carnet'] ?? null,
                'nombre' => $data['nombre'] ?? null,
                'comentario' => $data['comentario'] ?? $compra->comentario,
                'total' => $total,
                'retencion_porcentaje' => $data['retencion_porcentaje'] ?? 0,
                'tipo_pago' => $data['tipo_pago'] ?? 'EFECTIVO',
                'nro_factura' => $data['nro_factura'] ?? null,
                'categoria_programatica' => $data['categoria_programatica'] ?? null,
                'orden_de_compra' => $data['orden_de_compra'] ?? null,
                'metodo_orden' => $data['metodo_orden'] ?? null,
                'fecha_orden' => $data['fecha_orden'] ?? null,
                'codigo_interno' => $data['codigo_interno'] ?? null,
                'hoja_de_ruta' => $data['hoja_de_ruta'] ?? null,
            ]);

            $compra->detalles()->delete();

            foreach ($data['items'] as $item) {
                $producto = $productos[$item['producto_id']];
                $precio = (float) ($item['precio'] ?? $producto->precio_unitario ?? 0);
                $cantidad = (float) $item['cantidad'];
                $factor = (float) ($item['factor'] ?? 1.25);
                $precio13 = round($precio * $factor, 2);

                $compra->detalles()->create([
                    'user_id' => $request->user()->id,
                    'proveedor_id' => $data['proveedor_id'] ?? null,
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'precio' => $precio,
                    'cantidad' => $cantidad,
                    'cantidad_venta' => $cantidadesVenta[$producto->id] ?? 0,
                    'total' => round($precio * $cantidad, 2),
                    'factor' => $factor,
                    'precio13' => $precio13,
                    'total13' => round($precio13 * $cantidad, 2),
                    'precio_venta' => $item['precio_venta'] ?? $precio13,
                    'estado' => 'ACTIVO',
                    'lote' => $item['lote'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                    'nro_factura' => $data['nro_factura'] ?? null,
                ]);

                if ($data['tipo_registro'] === 'ENTRADA' && $precio > 0) {
                    $producto->update(['precio_unitario' => $precio]);
                }
            }

            return response()->json($compra->load(['proveedor', 'user:id,name', 'detalles.producto']));
        });
    }

    public function printPdf($id)
    {
        //        if (auth()->check() && method_exists(auth()->user(), 'can') && !auth()->user()->can('Imprimir Compras')) {
        //            abort(403, 'No autorizado para imprimir esta compra');
        //        }

        $compra = Compra::with(['proveedor', 'unidad', 'user:id,name,firma,sello,mostrar_firma,mostrar_sello', 'detalles.producto.subpartida'])->findOrFail($id);

        $pdf = Pdf::loadView('reportes.compra_detalle', [
            'compra' => $compra,
        ])->setPaper('letter', 'portrait');

        $filename = 'compra_'.$compra->id.'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->stream($filename);
    }

    public function destroy($id)
    {
        $compra = Compra::with('detalles')->findOrFail($id);

        $vendido = (float) $compra->detalles->sum('cantidad_venta');
        if ($vendido > 0) {
            return response()->json([
                'message' => 'No se puede anular: ya se vendieron productos de esta compra.',
            ], 422);
        }

        $detalleIds = $compra->detalles->pluck('id');

        // Registros nuevos (con despacho_detalle_reales)
        $enDespachoActivo = \App\Models\DespachoDetalleReal::whereIn('compra_detalle_id', $detalleIds)
            ->whereHas('despacho', fn ($q) => $q->where('estado', '!=', 'ANULADO'))
            ->exists();

        // Compatibilidad: despachos anteriores que aún usan compra_detalle_id en despacho_detalles
        if (! $enDespachoActivo) {
            $enDespachoActivo = \App\Models\DespachoDetalle::whereIn('compra_detalle_id', $detalleIds)
                ->whereHas('despacho', fn ($q) => $q->where('estado', '!=', 'ANULADO'))
                ->doesntHave('reales')
                ->exists();
        }

        if ($enDespachoActivo) {
            return response()->json([
                'message' => 'No se puede anular: productos de esta compra ya fueron despachados.',
            ], 422);
        }

        $compra->update(['estado' => 'ANULADO']);
        $compra->detalles()->update(['estado' => 'ANULADO']);

        return response()->json(['message' => 'Compra anulada correctamente']);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('producto_id')) {
            $productoId = $request->input('producto_id');
            $query->whereHas('detalles', function ($q) use ($productoId) {
                $q->where('producto_id', $productoId);
            })->with(['detalles' => function ($q) use ($productoId) {
                $q->where('producto_id', $productoId)
                    ->select(['id', 'compra_id', 'nombre', 'cantidad', 'cantidad_venta', 'precio', 'total', 'lote', 'fecha_vencimiento']);
            }]);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('fecha_hora', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('fecha_hora', '<=', $request->date_to);
        }

        if ($request->filled('tipo_registro')) {
            $query->where('tipo_registro', $request->tipo_registro);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('nro_factura', 'like', "%{$q}%")
                    ->orWhereHas('proveedor', fn ($query) => $query->where('nombre', 'like', "%{$q}%"));
            });
        }
    }
}
