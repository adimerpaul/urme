<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Solicitude;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PacienteController extends Controller
{
    public function buscarPorNN_RN(Request $request)
    {
        return $this->generarNombrePorTipo($request->get('tipo'));
    }

    public function buscarPorTipoNN_RN(string $tipo)
    {
        return $this->generarNombrePorTipo($tipo);
    }

    private function generarNombrePorTipo(?string $tipo)
    {
        if (!in_array($tipo, ['NN', 'RN'])) {
            return response()->json(['error' => 'Tipo inválido'], 422);
        }

        $count = Paciente::where('nombre_completo', 'LIKE', $tipo . '-%')->count();

        return $tipo . '-' . ($count + 1);
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $search  = $request->get('search');

        $query = Paciente::orderBy('id', 'desc');

        if ($search) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('nombre_completo', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%")
                    ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function show($id)
    {
        return Paciente::findOrFail($id);
    }

    public function store(Request $request)
    {
        $request->validate(['nombre_completo' => 'required|string|max:255']);

        $existe = Paciente::where('ci', $request->ci)->first();
        if ($existe && !empty($request->ci)) {
            return response()->json(['message' => 'El paciente con CI ' . $request->ci . ' ya existe'], 409);
        }

        $datos = $request->all();
        $datos['codigo'] = Paciente::generarCodigo($datos['nombre_completo'] ?? null, $datos['fecha_nac'] ?? null);
        return response()->json(Paciente::create($datos), 201);
    }

    public function update(Request $request, $id)
    {
        $paciente = Paciente::findOrFail($id);
        $paciente->update($request->all());
        return response()->json($paciente);
    }

    public function destroy($id)
    {
        Paciente::findOrFail($id)->delete();
        return response()->json(['message' => 'Paciente eliminado correctamente']);
    }

    public function buscarPorCi($ci)
    {
        $paciente = Paciente::where('ci', $ci)->first();
        if (!$paciente) {
            return response()->json(['message' => 'Paciente no encontrado'], 404);
        }
        return response()->json($paciente);
    }

    // ── Histórico de solicitudes de un paciente ──────────────────────────────

    private function historicQuery($pacienteId, $dateFrom = null, $dateTo = null)
    {
        return Solicitude::where('paciente_id', $pacienteId)
            ->whereNull('solicitudes.deleted_at')
            ->when($dateFrom, fn($q) => $q->whereDate('solicitudes.fecha_creacion', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('solicitudes.fecha_creacion', '<=', $dateTo))
            ->select(
                'solicitudes.id',
                'solicitudes.codigo_solicitud',
                'solicitudes.nro_registro',
                'solicitudes.tipo_atencion',
                'solicitudes.estado',
                'solicitudes.fecha_creacion',
                'solicitudes.hora_solicitud',
                'solicitudes.doctor_nombre',
                'solicitudes.sala',
                'solicitudes.cama',
                DB::raw('(SELECT e.nombre FROM establecimientos e WHERE e.id = solicitudes.establecimiento_id LIMIT 1) as establecimiento_nombre'),
                DB::raw('(SELECT us.nombre FROM unidad_solicitantes us WHERE us.id = solicitudes.unidad_solicitante_id LIMIT 1) as unidad_solicitante'),
                DB::raw('(SELECT COUNT(*) FROM servicio_solicitudes ss WHERE ss.solicitude_id = solicitudes.id) as cant_servicios'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT a.name ORDER BY a.name SEPARATOR ", ") FROM servicio_solicitudes ss JOIN areas a ON a.id = ss.area_id WHERE ss.solicitude_id = solicitudes.id) as areas'),
                DB::raw('(SELECT GROUP_CONCAT(ss.nombre SEPARATOR ", ") FROM servicio_solicitudes ss WHERE ss.solicitude_id = solicitudes.id) as pruebas'),
                DB::raw('(SELECT COUNT(*) FROM servicio_solicitudes ss WHERE ss.solicitude_id = solicitudes.id AND ss.realizado != "PENDIENTE") as cant_realizados')
            )
            ->orderByDesc('solicitudes.fecha_creacion')
            ->orderByDesc('solicitudes.id');
    }

    public function historico(Request $request, $id)
    {
        $paciente  = Paciente::findOrFail($id);
        $perPage   = min((int) $request->get('per_page', 15), 100);
        $dateFrom  = $request->get('date_from');
        $dateTo    = $request->get('date_to');
        $solicitudes = $this->historicQuery($id, $dateFrom, $dateTo)->paginate($perPage);

        return response()->json([
            'paciente'    => $paciente,
            'solicitudes' => $solicitudes,
        ]);
    }

    public function historicoExcel(Request $request, $id)
    {
        $paciente = Paciente::findOrFail($id);
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $rows     = $this->historicQuery($id, $dateFrom, $dateTo)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Histórico');

        // ── Encabezado del paciente ──
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'HISTÓRICO CLÍNICO DEL PACIENTE');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0D47A1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // ── Info del paciente ──
        $infoRows = [
            ['Nombre completo', $paciente->nombre_completo, 'Código', $paciente->codigo],
            ['CI',              $paciente->ci ?? '—',       'Género', $paciente->genero ?? '—'],
            ['Fecha nacimiento', $paciente->fecha_nac ?? '—', 'Teléfono', $paciente->telefono ?? '—'],
            ['Dirección', $paciente->direccion ?? '—', 'Total solicitudes', $rows->count()],
        ];

        $infoRow = 2;
        foreach ($infoRows as $info) {
            $sheet->setCellValue("A{$infoRow}", $info[0]);
            $sheet->setCellValue("B{$infoRow}", $info[1]);
            $sheet->setCellValue("D{$infoRow}", $info[2]);
            $sheet->setCellValue("E{$infoRow}", $info[3]);
            $sheet->getStyle("A{$infoRow}")->getFont()->setBold(true);
            $sheet->getStyle("D{$infoRow}")->getFont()->setBold(true);
            $infoRow++;
        }

        // ── Separador ──
        $headerRow = $infoRow + 1;
        $cols = ['A','B','C','D','E','F','G','H','I','J','K','L'];
        $lastCol = end($cols);

        // ── Cabecera de tabla ──
        $headers = [
            'Fecha', 'Hora', 'Cód. Solicitud', 'Nro Registro',
            'Doctor', 'Tipo Prestación',
            'Establecimiento', 'Unidad / Sala', 'Cama',
            'Áreas', 'Pruebas / Servicios', 'Estado',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, $headerRow, $h);
        }
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->freezePane("A" . ($headerRow + 1));

        // ── Filas de datos ──
        $dataRow = $headerRow + 1;
        foreach ($rows as $r) {
            $fecha = $r->fecha_creacion ? substr($r->fecha_creacion, 0, 10) : '';
            $salaCama = trim(($r->sala ? 'Sala: ' . $r->sala : '') . ($r->cama ? '  Cama: ' . $r->cama : ''));
            $unidad = trim(($r->unidad_solicitante ?? '') . ($r->sala ? ' / ' . $r->sala : ''));

            $sheet->setCellValueByColumnAndRow(1,  $dataRow, $fecha);
            $sheet->setCellValueByColumnAndRow(2,  $dataRow, $r->hora_solicitud);
            $sheet->setCellValueByColumnAndRow(3,  $dataRow, $r->codigo_solicitud);
            $sheet->setCellValueByColumnAndRow(4,  $dataRow, $r->nro_registro);
            $sheet->setCellValueByColumnAndRow(5,  $dataRow, $r->doctor_nombre);
            $sheet->setCellValueByColumnAndRow(6,  $dataRow, $r->tipo_atencion);
            $sheet->setCellValueByColumnAndRow(7,  $dataRow, $r->establecimiento_nombre);
            $sheet->setCellValueByColumnAndRow(8,  $dataRow, $unidad);
            $sheet->setCellValueByColumnAndRow(9,  $dataRow, $r->cama);
            $sheet->setCellValueByColumnAndRow(10, $dataRow, $r->areas);
            $sheet->setCellValueByColumnAndRow(11, $dataRow, $r->pruebas);
            $sheet->setCellValueByColumnAndRow(12, $dataRow, $r->estado);

            if ($dataRow % 2 === 0) {
                $sheet->getStyle("A{$dataRow}:{$lastCol}{$dataRow}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEAF2FF']],
                ]);
            }
            $dataRow++;
        }

        // ── Borde en toda la tabla ──
        $sheet->getStyle("A{$headerRow}:{$lastCol}" . ($dataRow - 1))->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBDBDBD']],
            ],
        ]);

        // ── Autosize ──
        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $filename = "historico_{$paciente->id}_{$paciente->nombre_completo}.xlsx";
        $path = storage_path('app/' . $filename);
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
