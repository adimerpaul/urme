<?php

namespace App\Http\Controllers;

use App\Models\AlmacenItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AlmacenItemController extends Controller
{
    public function index(Request $request)
    {
        $query = AlmacenItem::with('subpartida.partida.grupo')
            ->select('almacen_items.*')
            ->selectSub($this->cantidadSubquery(), 'cantidad');

        $this->applyFilters($query, $request);

        if ($request->boolean('existente')) {
            $query->having('cantidad', '>', 0);
        }

        $sortBy = $request->input('sortBy', 'nombre');
        $descending = filter_var($request->input('descending', false), FILTER_VALIDATE_BOOLEAN);
        $rowsPerPage = (int) $request->input('rowsPerPage', 15);

        if (! in_array($sortBy, ['nombre', 'unidad_medida', 'precio_unitario', 'cantidad', 'id'], true)) {
            $sortBy = 'nombre';
        }

        $query->orderBy($sortBy, $descending ? 'desc' : 'asc');
        $summary = $this->summary($request);

        if ($rowsPerPage <= 0) {
            return response()->json([
                'data' => $query->get(),
                'summary' => $summary,
            ]);
        }

        $paginated = $query->paginate($rowsPerPage);
        $response = $paginated->toArray();
        $response['summary'] = $summary;

        return response()->json($response);
    }

    public function show($id)
    {
        return AlmacenItem::with('subpartida.partida.grupo')->findOrFail($id);
    }

    public function reportPdf(Request $request)
    {
        return $this->buildReportPdf($request);
    }

    public function reportExcel(Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $existente = $request->boolean('existente');

        $query = $this->reportQuery($request);
        if ($existente) {
            $query->havingRaw('cantidad > 0');
        }
        $items   = $query->orderBy('grupos.nombre')->orderBy('partidas.nombre')
                         ->orderBy('subpartidas.nombre')->orderBy('almacen_items.nombre')
                         ->get();
        $filters = $this->filterLabels($request);
        $title   = $existente ? 'INVENTARIO EXISTENTE' : 'INVENTARIO COMPLETO';

        // ── Colores ───────────────────────────────────────────────────
        $cAzul     = 'FF1A237E';
        $cAzulMed  = 'FF283593';
        $cAzulCla  = 'FFE8EAF6';
        $cGrisCab  = 'FF455A64';
        $cVerde    = 'FF1B5E20';
        $cVerdeClaro = 'FFE8F5E9';
        $cAmarillo = 'FFFFF9C4';
        $cBlanco   = 'FFFFFFFF';
        $cGrisFila = 'FFF5F5F5';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventario');

        // Definir anchos de columnas
        // A=Nro  B=Nombre  C=Unidad  D=P.U.  E=Cantidad  F=Valor Total  G=Grupo  H=Partida  I=Subpartida
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(48);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(13);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(30);
        $sheet->getColumnDimension('H')->setWidth(30);
        $sheet->getColumnDimension('I')->setWidth(30);

        // ── Fila 1: Título ─────────────────────────────────────────────
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'LABORATORIO CLÍNICO SIL — '.$title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold'=>true, 'size'=>14, 'color'=>['argb'=>$cBlanco]],
            'fill'      => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$cAzul]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Fila 2: Meta info ──────────────────────────────────────────
        $sheet->mergeCells('A2:I2');
        $metaInfo = 'Generado: '.now()->format('d/m/Y H:i')
            .'   |   Grupo: '.$filters['grupo']
            .'   |   Partida: '.$filters['partida']
            .'   |   Subpartida: '.$filters['subpartida'];
        if ($filters['busqueda'] !== 'Sin busqueda') {
            $metaInfo .= '   |   Búsqueda: '.$filters['busqueda'];
        }
        $sheet->setCellValue('A2', $metaInfo);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic'=>true, 'size'=>9, 'color'=>['argb'=>'FF333333']],
            'fill'      => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$cAzulCla]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_LEFT],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(14);

        // ── Fila 3: Totales resumen ────────────────────────────────────
        $totalItems    = $items->count();
        $totalCantidad = $items->sum('cantidad');
        $totalValor    = $items->sum(fn ($i) => $i->cantidad * $i->precio_unitario);

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', "Total ítems: {$totalItems}");
        $sheet->setCellValue('D3', 'Existencia total:');
        $sheet->setCellValue('E3', $totalCantidad);
        $sheet->setCellValue('F3', $totalValor);
        $sheet->getStyle('A3:I3')->applyFromArray([
            'font' => ['bold'=>true, 'size'=>9, 'color'=>['argb'=>$cBlanco]],
            'fill' => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$cGrisCab]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('F3')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
        $sheet->getRowDimension(3)->setRowHeight(14);

        // ── Fila 4: Cabeceras ──────────────────────────────────────────
        $headers = ['A'=>'Nro', 'B'=>'Nombre del Ítem', 'C'=>'Unidad', 'D'=>'P. Unitario (Bs)',
                    'E'=>'Cantidad', 'F'=>'Valor Total (Bs)', 'G'=>'Grupo', 'H'=>'Partida', 'I'=>'Subpartida'];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}4", $label);
        }
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font'      => ['bold'=>true, 'size'=>9, 'color'=>['argb'=>$cBlanco]],
            'fill'      => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$cAzulMed]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER, 'wrapText'=>true],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['argb'=>'FF90A4AE']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->setAutoFilter('A4:I4');
        $sheet->freezePane('A5');

        // ── Filas de datos ─────────────────────────────────────────────
        $row = 5;
        $grupoActual = null;

        foreach ($items as $i => $item) {
            $cantidad   = (float) $item->cantidad;
            $precio     = (float) $item->precio_unitario;
            $valorTotal = round($cantidad * $precio, 2);

            // Separador de grupo
            if ($item->grupo_nombre !== $grupoActual) {
                $grupoActual = $item->grupo_nombre;
                $sheet->mergeCells("A{$row}:I{$row}");
                $sheet->setCellValue("A{$row}", '  '.strtoupper($item->grupo_codigo.' — '.$item->grupo_nombre));
                $sheet->getStyle("A{$row}")->applyFromArray([
                    'font' => ['bold'=>true, 'size'=>8, 'color'=>['argb'=>$cBlanco]],
                    'fill' => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$cGrisCab]],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(13);
                $row++;
            }

            $bg = $cantidad > 0
                ? ($i % 2 === 0 ? $cBlanco : $cGrisFila)
                : $cAmarillo; // Sin stock → amarillo claro

            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $item->nombre);
            $sheet->setCellValue("C{$row}", $item->unidad_medida);
            $sheet->setCellValue("D{$row}", $precio);
            $sheet->setCellValue("E{$row}", $cantidad);
            $sheet->setCellValue("F{$row}", $valorTotal);
            $sheet->setCellValue("G{$row}", $item->grupo_nombre);
            $sheet->setCellValue("H{$row}", $item->partida_nombre);
            $sheet->setCellValue("I{$row}", $item->subpartida_nombre);

            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'fill'    => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$bg]],
                'borders' => ['allBorders'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['argb'=>'FFDDDDDD']]],
                'font'    => ['size'=>8],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("E{$row}")->getFont()->setBold($cantidad > 0);
            if ($cantidad > 0) {
                $sheet->getStyle("E{$row}")->getFont()->getColor()->setARGB($cVerde);
            }
            $sheet->getStyle("D{$row}:F{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
            $sheet->getRowDimension($row)->setRowHeight(13);
            $row++;
        }

        // ── Fila de totales final ──────────────────────────────────────
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'TOTALES');
        $sheet->setCellValue("D{$row}", '');
        $sheet->setCellValue("E{$row}", $totalCantidad);
        $sheet->setCellValue("F{$row}", $totalValor);
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font'      => ['bold'=>true, 'size'=>9, 'color'=>['argb'=>$cBlanco]],
            'fill'      => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$cAzul]],
            'alignment' => ['horizontal'=>Alignment::HORIZONTAL_RIGHT],
            'borders'   => ['allBorders'=>['borderStyle'=>Border::BORDER_MEDIUM, 'color'=>['argb'=>'FF90A4AE']]],
        ]);
        $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $filename = ($existente ? 'inventario_existente' : 'inventario_completo').'_'.now()->format('Ymd_His').'.xlsx';
        $path     = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function dashboard(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $user     = $request->user();

        // Verificar si puede ver todos los pedidos
        $canVerTodos = $user && (
            $user->role === 'Administrador' ||
            $user->hasPermissionTo('Ver todos los pedidos')
        );

        $inventoryQuery = $this->reportQuery(new Request);
        $inventory = DB::query()
            ->fromSub(clone $inventoryQuery, 'inventory')
            ->selectRaw('COUNT(*) as items')
            ->selectRaw('COALESCE(SUM(cantidad), 0) as existencia_total')
            ->selectRaw('SUM(CASE WHEN cantidad > 0 THEN 1 ELSE 0 END) as items_con_existencia')
            ->selectRaw('SUM(CASE WHEN cantidad <= 0 THEN 1 ELSE 0 END) as items_sin_existencia')
            ->first();

        $pedidosQuery = DB::table('pedidos')->whereNull('deleted_at');

        // Sin permiso → solo sus propios pedidos
        if (! $canVerTodos && $user) {
            $pedidosQuery->where('user_id', $user->id);
        }

        if ($dateFrom) {
            $pedidosQuery->whereDate('fecha_hora', '>=', $dateFrom);
        }
        if ($dateTo) {
            $pedidosQuery->whereDate('fecha_hora', '<=', $dateTo);
        }

        $despachosQuery = DB::table('despachos')->whereNull('deleted_at');
        if ($dateFrom) {
            $despachosQuery->whereDate('fecha_entrega', '>=', $dateFrom);
        }
        if ($dateTo) {
            $despachosQuery->whereDate('fecha_entrega', '<=', $dateTo);
        }

        $solicitudesQuery = DB::table('solicitudes')->whereNull('deleted_at');
        if ($dateFrom) {
            $solicitudesQuery->whereDate('fecha_creacion', '>=', $dateFrom);
        }
        if ($dateTo) {
            $solicitudesQuery->whereDate('fecha_creacion', '<=', $dateTo);
        }

        $pedidosPorEstado = (clone $pedidosQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $despachosPorEstado = (clone $despachosQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $solicitudesPorEstado = (clone $solicitudesQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $seriePedidos = (clone $pedidosQuery)
            ->select(DB::raw('DATE(fecha_hora) as fecha'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('fecha_hora')
            ->groupBy(DB::raw('DATE(fecha_hora)'))
            ->orderBy('fecha')
            ->get();

        $serieDespachos = (clone $despachosQuery)
            ->select(DB::raw('DATE(fecha_entrega) as fecha'), DB::raw('COUNT(*) as total'))
            ->whereNotNull('fecha_entrega')
            ->groupBy(DB::raw('DATE(fecha_entrega)'))
            ->orderBy('fecha')
            ->get();

        $ultimosPedidos = (clone $pedidosQuery)
            ->select('id', 'fecha_hora', 'nombre_usuario', 'estado', 'total')
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $ultimosDespachos = (clone $despachosQuery)
            ->select('id', 'nro', 'pedido_id', 'fecha_entrega', 'solicitante', 'servicio', 'estado', 'total')
            ->orderByDesc('fecha_entrega')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $inventarioCritico = DB::query()
            ->fromSub(clone $inventoryQuery, 'inventory')
            ->select('id', 'nombre', 'unidad_medida', 'cantidad', 'precio_unitario')
            ->orderBy('cantidad')
            ->orderBy('nombre')
            ->limit(10)
            ->get();

        return response()->json([
            'can_ver_todos' => $canVerTodos,
            'resumen' => [
                'pedidos' => (int) (clone $pedidosQuery)->count(),
                'pedidos_pendientes' => (int) ($pedidosPorEstado['PENDIENTE'] ?? 0),
                'despachos' => (int) (clone $despachosQuery)->count(),
                'despachos_anulados' => (int) ($despachosPorEstado['ANULADO'] ?? 0),
                'solicitudes' => (int) (clone $solicitudesQuery)->count(),
                'inventario_items' => (int) ($inventory->items ?? 0),
                'existencia_total' => (float) ($inventory->existencia_total ?? 0),
                'items_con_existencia' => (int) ($inventory->items_con_existencia ?? 0),
                'items_sin_existencia' => (int) ($inventory->items_sin_existencia ?? 0),
                'total_pedidos_bs' => (float) (clone $pedidosQuery)->sum('total'),
                'total_despachos_bs' => (float) (clone $despachosQuery)->where('estado', '!=', 'ANULADO')->sum('total'),
            ],
            'pedidos_por_estado' => $pedidosPorEstado,
            'despachos_por_estado' => $despachosPorEstado,
            'solicitudes_por_estado' => $solicitudesPorEstado,
            'serie_pedidos' => $seriePedidos,
            'serie_despachos' => $serieDespachos,
            'ultimos_pedidos' => $ultimosPedidos,
            'ultimos_despachos' => $ultimosDespachos,
            'inventario_critico' => $inventarioCritico,
        ]);
    }

    private function buildReportPdf(Request $request)
    {
        $existente = $request->boolean('existente');
        @set_time_limit(240);
        @ini_set('memory_limit', '768M');

        $query = $this->reportQuery($request);

        if ($existente) {
            $query->havingRaw('cantidad > 0');
        }

        $items = $query
            ->orderBy('almacen_items.nombre')
            ->get();

        $summary = $this->summary($request);
        $filters = $this->filterLabels($request);
        $title = $existente ? 'Inventario existente' : 'Inventario completo';

        $pdf = Pdf::loadView('reportes.almacen_inventario', [
            'items' => $items,
            'summary' => $summary,
            'filters' => $filters,
            'title' => $title,
            'existente' => $existente,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream(($existente ? 'inventario_existente' : 'inventario_completo').'_'.now()->format('Ymd_His').'.pdf');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['imagen'] = $this->saveImage($request);

        $item = AlmacenItem::create($data);

        return response()->json($item->load('subpartida.partida.grupo'), 201);
    }

    public function update(Request $request, $id)
    {
        $item = AlmacenItem::findOrFail($id);
        $data = $request->validate($this->rules(true));
        if ($request->hasFile('imagen')) {
            $data['imagen'] = $this->saveImage($request);
        }
        $item->update($data);

        return response()->json($item->load('subpartida.partida.grupo'));
    }

    public function updateImagen(Request $request, $id)
    {
        $item = AlmacenItem::findOrFail($id);

        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $item->update(['imagen' => $this->saveImage($request)]);

        return response()->json(['imagen' => $item->imagen]);
    }

    public function destroy($id)
    {
        $item = AlmacenItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Item eliminado correctamente']);
    }

    private function rules(bool $updating = false): array
    {
        $required = $updating ? 'sometimes|required' : 'required';

        return [
            'subpartida_id' => "{$required}|exists:subpartidas,id",
            'nombre' => "{$required}|string|max:255",
            'unidad_medida' => 'nullable|string|max:100',
            'precio_unitario' => 'nullable|numeric|min:0',
            'imagen' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }

    private function saveImage(Request $request): string
    {
        if (! $request->hasFile('imagen')) {
            return 'default.png';
        }

        $directory = public_path('images/productos');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = uniqid('producto_', true).'.png';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $manager = new ImageManager(new Driver);

        $manager->read($request->file('imagen')->getPathname())
            ->cover(420, 420)
            ->toPng()
            ->save($path);

        return $filename;
    }

    private function userSubpartidaIds(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [];
        }

        return DB::table('subpartida_user')
            ->where('user_id', $user->id)
            ->pluck('subpartida_id')
            ->toArray();
    }

    private function applyFilters($query, Request $request): void
    {
        // Filtrar por subpartidas del usuario solo en contexto de pedidos
        if ($request->boolean('solo_mis_subpartidas')) {
            $query->whereIn('subpartida_id', $this->userSubpartidaIds($request));
        }

        if ($request->filled('grupo_id')) {
            $query->whereHas('subpartida.partida', function ($query) use ($request) {
                $query->where('grupo_id', $request->grupo_id);
            });
        }

        if ($request->filled('partida_id')) {
            $query->whereHas('subpartida', function ($query) use ($request) {
                $query->where('partida_id', $request->partida_id);
            });
        }

        if ($request->filled('subpartida_id')) {
            $query->where('subpartida_id', $request->subpartida_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%")
                    ->orWhere('unidad_medida', 'like', "%{$q}%")
                    ->orWhereHas('subpartida', function ($query) use ($q) {
                        $query->where('codigo', 'like', "%{$q}%")
                            ->orWhere('nombre', 'like', "%{$q}%");
                    })
                    ->orWhereHas('subpartida.partida', function ($query) use ($q) {
                        $query->where('codigo', 'like', "%{$q}%")
                            ->orWhere('nombre', 'like', "%{$q}%");
                    })
                    ->orWhereHas('subpartida.partida.grupo', function ($query) use ($q) {
                        $query->where('codigo', 'like', "%{$q}%")
                            ->orWhere('nombre', 'like', "%{$q}%");
                    });
            });
        }
    }

    private function cantidadSubquery()
    {
        return DB::table('compra_detalles')
            ->join('compras', 'compras.id', '=', 'compra_detalles.compra_id')
            ->selectRaw($this->cantidadExpression())
            ->whereColumn('compra_detalles.producto_id', 'almacen_items.id')
            ->whereNull('compra_detalles.deleted_at')
            ->whereNull('compras.deleted_at')
            ->where('compras.estado', 'ACTIVO')
            ->whereRaw("UPPER(COALESCE(compra_detalles.estado, '')) = 'ACTIVO'");
    }

    private function cantidadExpression(string $detalleAlias = 'compra_detalles', string $compraAlias = 'compras'): string
    {
        $despachos = $this->despachoSalidaExpression();

        return "COALESCE(SUM(CASE WHEN {$compraAlias}.id IS NULL THEN 0 WHEN {$compraAlias}.tipo_registro = 'SALIDA' THEN -COALESCE({$detalleAlias}.cantidad, 0) ELSE COALESCE({$detalleAlias}.cantidad, 0) - COALESCE({$detalleAlias}.cantidad_venta, 0) END), 0) - {$despachos}";
    }

    private function despachoSalidaExpression(): string
    {
        // Solo cuenta filas de despacho_detalles sin compra_detalle_id Y sin registros en
        // despacho_detalle_reales. Las filas con reales ya están descontadas vía cantidad_venta (PEPS).
        return "COALESCE((SELECT SUM(dd.cantidad) FROM despacho_detalles dd INNER JOIN despachos d ON d.id = dd.despacho_id WHERE dd.almacen_item_id = almacen_items.id AND dd.compra_detalle_id IS NULL AND dd.deleted_at IS NULL AND d.estado <> 'ANULADO' AND d.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM despacho_detalle_reales ddr WHERE ddr.despacho_detalle_id = dd.id AND ddr.deleted_at IS NULL)), 0)";
    }

    private function summary(Request $request): array
    {
        $inventoryQuery = $this->reportQuery($request);

        if ($request->boolean('existente')) {
            $inventoryQuery->havingRaw('cantidad > 0');
        }

        $row = DB::query()
            ->fromSub($inventoryQuery, 'inventory')
            ->selectRaw('COUNT(*) as items')
            ->selectRaw('COALESCE(SUM(cantidad), 0) as cantidad')
            ->first();

        return [
            'items' => (int) ($row->items ?? 0),
            'cantidad' => (float) ($row->cantidad ?? 0),
        ];
    }

    private function reportQuery(Request $request)
    {
        $cantidad = $this->cantidadExpression();

        $query = DB::table('almacen_items')
            ->leftJoin('compra_detalles', function ($join) {
                $join->on('compra_detalles.producto_id', '=', 'almacen_items.id')
                    ->whereNull('compra_detalles.deleted_at')
                    ->whereRaw("UPPER(COALESCE(compra_detalles.estado, '')) = 'ACTIVO'");
            })
            ->leftJoin('compras', function ($join) {
                $join->on('compras.id', '=', 'compra_detalles.compra_id')
                    ->whereNull('compras.deleted_at')
                    ->where('compras.estado', '=', 'ACTIVO');
            })
            ->join('subpartidas', 'subpartidas.id', '=', 'almacen_items.subpartida_id')
            ->join('partidas', 'partidas.id', '=', 'subpartidas.partida_id')
            ->join('grupos', 'grupos.id', '=', 'partidas.grupo_id')
            ->whereNull('almacen_items.deleted_at');

        if ($request->filled('grupo_id')) {
            $query->where('partidas.grupo_id', $request->grupo_id);
        }

        if ($request->filled('partida_id')) {
            $query->where('subpartidas.partida_id', $request->partida_id);
        }

        if ($request->filled('subpartida_id')) {
            $query->where('almacen_items.subpartida_id', $request->subpartida_id);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('almacen_items.nombre', 'like', "%{$q}%")
                    ->orWhere('almacen_items.unidad_medida', 'like', "%{$q}%")
                    ->orWhere('subpartidas.codigo', 'like', "%{$q}%")
                    ->orWhere('subpartidas.nombre', 'like', "%{$q}%")
                    ->orWhere('partidas.codigo', 'like', "%{$q}%")
                    ->orWhere('partidas.nombre', 'like', "%{$q}%")
                    ->orWhere('grupos.codigo', 'like', "%{$q}%")
                    ->orWhere('grupos.nombre', 'like', "%{$q}%");
            });
        }

        return $query
            ->select([
                'almacen_items.id',
                'almacen_items.nombre',
                'almacen_items.unidad_medida',
                'almacen_items.precio_unitario',
                'almacen_items.imagen',
                'subpartidas.codigo as subpartida_codigo',
                'subpartidas.nombre as subpartida_nombre',
                'partidas.codigo as partida_codigo',
                'partidas.nombre as partida_nombre',
                'grupos.codigo as grupo_codigo',
                'grupos.nombre as grupo_nombre',
            ])
            ->selectRaw("{$cantidad} as cantidad")
            ->groupBy(
                'almacen_items.id',
                'almacen_items.nombre',
                'almacen_items.unidad_medida',
                'almacen_items.precio_unitario',
                'almacen_items.imagen',
                'subpartidas.codigo',
                'subpartidas.nombre',
                'partidas.codigo',
                'partidas.nombre',
                'grupos.codigo',
                'grupos.nombre',
            );
    }

    private function filterLabels(Request $request): array
    {
        $filters = [
            'grupo' => 'Todos',
            'partida' => 'Todas',
            'subpartida' => 'Todas',
            'busqueda' => $request->input('q') ?: 'Sin busqueda',
        ];

        if ($request->filled('grupo_id')) {
            $row = DB::table('grupos')->where('id', $request->grupo_id)->first();
            if ($row) {
                $filters['grupo'] = "{$row->codigo} - {$row->nombre}";
            }
        }

        if ($request->filled('partida_id')) {
            $row = DB::table('partidas')->where('id', $request->partida_id)->first();
            if ($row) {
                $filters['partida'] = "{$row->codigo} - {$row->nombre}";
            }
        }

        if ($request->filled('subpartida_id')) {
            $row = DB::table('subpartidas')->where('id', $request->subpartida_id)->first();
            if ($row) {
                $filters['subpartida'] = "{$row->codigo} - {$row->nombre}";
            }
        }

        return $filters;
    }
}
