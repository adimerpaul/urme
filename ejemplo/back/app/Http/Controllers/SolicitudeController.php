<?php

namespace App\Http\Controllers;

use App\Models\PerfilImpresion;
use App\Models\ServicioSolicitude;
use App\Models\SolicitudeFormulario;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\ResultadoLaboratorio;
use App\Models\QuimicaSanguinea;
use App\Models\Servicio;
use App\Models\Solicitude;
use App\Models\Paciente;
use App\Models\Doctor;
use App\Models\SolitudePreAnalitica;
use App\Models\SolicitudePreAnaliticaComentario;
use App\Models\SolicitudePropiedad;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\UnidadSolicitante;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SolicitudeController extends Controller
{
    public function showTestEmbarazo($id)
    {
        $solicitud = Solicitude::with(['paciente', 'doctor'])->findOrFail($id);
        $quimica = QuimicaSanguinea::where('solicitude_id', $id)->first();

        return response()->json([
            'solicitud' => $solicitud,
            'test_embarazo' => $quimica?->test_embarazo,
            'quimica_code' => $quimica?->code,
        ]);
    }

    public function saveTestEmbarazo(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);

        $payload = $request->validate([
            'test_embarazo' => 'required|in:Positivo,Negativo',
        ]);

        $quimica = QuimicaSanguinea::updateOrCreate(
            ['solicitude_id' => $solicitud->id],
            [
                'test_embarazo' => $payload['test_embarazo'],
                'user_id' => $request->user()?->id,
            ]
        );

        return response()->json([
            'message' => 'Test de embarazo guardado correctamente.',
            'test_embarazo' => $quimica->test_embarazo,
            'quimica_code' => $quimica->code,
        ]);
    }

    public function printTestEmbarazo($id)
    {
        $quimica = QuimicaSanguinea::where('solicitude_id', $id)->firstOrFail();

        if (empty($quimica->test_embarazo)) {
            return response()->json(['message' => 'No existe test de embarazo registrado para esta solicitud.'], 422);
        }

        // Reutiliza exactamente el formato estándar de Química Sanguínea (QR, layout y nomenclatura actual)
        return app(\App\Http\Controllers\QuimicaSanguineaController::class)->pdfBySolicitude($quimica->code);
    }

    function actualizarCodigo(Request $request, $id){
        //    this.$axios.post(`solicitudes/${this.consentimiento.id}/actualizar-codigo`, {
//        codigo: this.consentimiento.codigo,
//        nro_registro: this.consentimiento.nro_registro
//      })
        $solicitud = Solicitude::findOrFail($id);
        if ($request->filled('codigo')) {
            $solicitud->codigo = $request->input('codigo');
        }
        if ($request->filled('nro_registro')) {
            $solicitud->nro_registro = $request->input('nro_registro');
        }
        $solicitud->codigo_solicitud = ($solicitud->nro_registro ?? '') . ($solicitud->codigo ?? '');
        $solicitud->save();

        return response()->json($solicitud->fresh());
    }
    public function pdfPreanalitica(Request $request)
    {
        $fecha  = $request->query('fecha');          // YYYY-MM-DD
        $filter = $request->query('filter', '');

        // si no mandan fecha, usa hoy
        if (!$fecha) {
            $fecha = now()->toDateString();
        }

        $query = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.tiposMuestra',
            'userPreanalitica',
        ])
            ->whereDate('fecha_creacion', $fecha)
            ->whereIn('estado', ['ATENDIENDO','MUESTRA RECHAZADA','ENVIADO_ANALITICA','ANALIZADO','MUESTRA NO TOMADA'])
            ->orderByRaw("FIELD(estado, 'ATENDIENDO', 'ENVIADO_ANALITICA', 'ANALIZADO', 'MUESTRA RECHAZADA', 'MUESTRA NO TOMADA') ASC")
            ->orderBy('id', 'desc');

        if (!empty($filter)) {
            $query->where(function ($q) use ($filter) {
                $q->where('paciente_nombre', 'like', "%$filter%")
                    ->orWhereHas('paciente', function ($q2) use ($filter) {
                        $q2->where('nombre_completo', 'like', "%$filter%")
                            ->orWhere('ci', 'like', "%$filter%");
                    })
                    ->orWhere('establecimiento_salud', 'like', "%$filter%")
                    ->orWhere('doctor_nombre', 'like', "%$filter%");
            });
        }

        $rows = $query->get();

        $pdf = Pdf::loadView('reportes.solicitudes_preanalitica', [
            'fecha' => $fecha,
            'filter' => $filter,
            'rows' => $rows,
            'generado' => now(),
        ])->setPaper('letter', 'landscape'); // igual que tu jsPDF landscape letter

        return $pdf->stream("solicitudes_preanalitica_{$fecha}.pdf");
    }
    function marcarMuestraNoTomada(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);
//        $solicitud->muestra_rechazada = 'Si';
        $solicitud->motivo_rechazo = $request->input('motivo_rechazo', 'No especificado');
//        $solicitud->fecha_rechazo_muestra = now();
        $solicitud->user_preanalitica_id = $request->user() ? $request->user()->id : null;
        $solicitud->estado = 'MUESTRA NO TOMADA';
        $solicitud->fecha_envio_analitica = now();
        $solicitud->save();

        return response()->json($solicitud->fresh());
    }
    public function reporteSolicitudesServicios(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $userId   = $request->get('user_id');
        $group    = $request->get('group', 'day');

        // servicio_id puede venir como: servicio_id=3 o servicio_id[]=3&servicio_id[]=5
        $servicioIds = $request->get('servicio_id', null);
        if ($servicioIds !== null && !is_array($servicioIds)) {
            $servicioIds = [$servicioIds];
        }
        $servicioIds = array_values(array_filter((array)$servicioIds, fn($x) => $x !== null && $x !== ''));
        $servicioIds = !empty($servicioIds) ? array_map('intval', $servicioIds) : [];

        // ---- query base: solicitudes (soft delete)
        $solQuery = DB::table('solicitudes as s')
            ->whereNull('s.deleted_at');

        if ($dateFrom) $solQuery->whereDate('s.fecha_creacion', '>=', $dateFrom);
        if ($dateTo)   $solQuery->whereDate('s.fecha_creacion', '<=', $dateTo);
        if ($userId)   $solQuery->where('s.user_id', $userId);

        // ---- base pivot servicio_solicitudes (soft delete pivot + solicitudes + servicios)
        $ssQuery = DB::table('servicio_solicitudes as ss')
            ->join('solicitudes as s', 's.id', '=', 'ss.solicitude_id')
            ->join('servicios as se', 'se.id', '=', 'ss.servicio_id')
            ->whereNull('s.deleted_at')
            ->whereNull('ss.deleted_at')
            ->whereNull('se.deleted_at');

        if ($dateFrom) $ssQuery->whereDate('s.fecha_creacion', '>=', $dateFrom);
        if ($dateTo)   $ssQuery->whereDate('s.fecha_creacion', '<=', $dateTo);
        if ($userId)   $ssQuery->where('s.user_id', $userId);
        if (!empty($servicioIds)) $ssQuery->whereIn('ss.servicio_id', $servicioIds);

        // ---- KPIs
        $totalSolicitudes  = (clone $solQuery)->count();
        $totalPrestaciones = (clone $ssQuery)->count();

        $promedioPrestaciones = $totalSolicitudes > 0
            ? round($totalPrestaciones / $totalSolicitudes, 1)
            : 0;

        // ---- expresiones de agrupación
        if ($group === 'month') {
            $keyExpr   = "DATE_FORMAT(s.fecha_creacion, '%Y-%m')";
            $labelExpr = "DATE_FORMAT(s.fecha_creacion, '%Y-%m')";
        } elseif ($group === 'week') {
            $keyExpr   = "YEARWEEK(s.fecha_creacion, 1)";
            $labelExpr = "CONCAT(YEAR(s.fecha_creacion), '-W', LPAD(WEEK(s.fecha_creacion, 1), 2, '0'))";
        } else { // day
            $keyExpr   = "DATE(s.fecha_creacion)";
            $labelExpr = "DATE(s.fecha_creacion)";
        }

        // ---- SERIE: solicitudes vs prestaciones (FIX ONLY_FULL_GROUP_BY)
        $serie = DB::table('solicitudes as s')
            ->leftJoin('servicio_solicitudes as ss', function ($join) {
                $join->on('ss.solicitude_id', '=', 's.id')
                    ->whereNull('ss.deleted_at');
            })
            ->whereNull('s.deleted_at')
            ->when($dateFrom, fn($q) => $q->whereDate('s.fecha_creacion', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('s.fecha_creacion', '<=', $dateTo))
            ->when($userId,   fn($q) => $q->where('s.user_id', $userId))
            ->when(!empty($servicioIds), fn($q) => $q->whereIn('ss.servicio_id', $servicioIds))
            ->selectRaw("$keyExpr as group_key")
            ->selectRaw("$labelExpr as label")
            ->selectRaw("COUNT(DISTINCT s.id) as solicitudes")
            ->selectRaw("COUNT(ss.id) as prestaciones")
            ->groupBy('group_key', 'label')
            ->orderBy('group_key', 'asc')
            ->get();

        // ---- TABLA POR USUARIO (solicitudes + prestaciones)
        $porUsuario = DB::table('users as u')
            ->join('solicitudes as s', 's.user_id', '=', 'u.id')
            ->leftJoin('servicio_solicitudes as ss', function ($join) {
                $join->on('ss.solicitude_id', '=', 's.id')
                    ->whereNull('ss.deleted_at');
            })
            ->whereNull('u.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, fn($q) => $q->whereDate('s.fecha_creacion', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('s.fecha_creacion', '<=', $dateTo))
            ->when($userId, fn($q) => $q->where('u.id', $userId))
            ->when(!empty($servicioIds), fn($q) => $q->whereIn('ss.servicio_id', $servicioIds))
            ->groupBy('u.id', 'u.name', 'u.username')
            ->select(
                'u.id as user_id',
                'u.name as user_name',
                'u.username',
                DB::raw('COUNT(DISTINCT s.id) as solicitudes'),
                DB::raw('COUNT(ss.id) as prestaciones')
            )
            ->orderByDesc('prestaciones')
            ->get();

        // ---- TOP PRESTACIONES (servicios) (histograma)
        $topPrestaciones = DB::table('servicio_solicitudes as ss')
            ->join('solicitudes as s', 's.id', '=', 'ss.solicitude_id')
            ->join('servicios as se', 'se.id', '=', 'ss.servicio_id')
            ->whereNull('s.deleted_at')
            ->whereNull('ss.deleted_at')
            ->whereNull('se.deleted_at')
            ->when($dateFrom, fn($q) => $q->whereDate('s.fecha_creacion', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('s.fecha_creacion', '<=', $dateTo))
            ->when($userId, fn($q) => $q->where('s.user_id', $userId))
            ->when(!empty($servicioIds), fn($q) => $q->whereIn('ss.servicio_id', $servicioIds))
            ->groupBy('se.id', 'se.nombre')
            ->select(
                'se.id as prestacion_id',
                'se.nombre as prestacion_nombre',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT s.id) as solicitudes')
            )
            ->orderByDesc('total')
            ->limit(12)
            ->get();

        // ---- ÚLTIMAS SOLICITUDES con conteo prestaciones
        $ultimas = DB::table('solicitudes as s')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, fn($q) => $q->whereDate('s.fecha_creacion', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('s.fecha_creacion', '<=', $dateTo))
            ->when($userId, fn($q) => $q->where('s.user_id', $userId))
            ->when(!empty($servicioIds), function ($q) use ($servicioIds) {
                $q->whereExists(function ($sub) use ($servicioIds) {
                    $sub->select(DB::raw(1))
                        ->from('servicio_solicitudes as ssf')
                        ->whereColumn('ssf.solicitude_id', 's.id')
                        ->whereNull('ssf.deleted_at')
                        ->whereIn('ssf.servicio_id', $servicioIds);
                });
            })
            ->select(
                's.id',
                's.nro_registro',
                's.codigo_solicitud',
                's.paciente_nombre',
                's.doctor_nombre',
                's.estado',
                's.fecha_creacion',
                's.hora_solicitud',
                'u.name as user_name',
                DB::raw('(
                SELECT COUNT(*)
                FROM servicio_solicitudes ss2
                WHERE ss2.solicitude_id = s.id
                  AND ss2.deleted_at IS NULL
            ) as cant_prestaciones')
            )
            ->orderByDesc('s.fecha_creacion')
            ->orderByDesc('s.id')
            ->limit(20)
            ->get();

        return response()->json([
            'resumen' => [
                'total_solicitudes' => $totalSolicitudes,
                'total_prestaciones' => $totalPrestaciones,
                'promedio_prestaciones' => $promedioPrestaciones,
            ],
            'por_usuario' => $porUsuario,
            'top_prestaciones' => $topPrestaciones,
            'serie' => $serie,
            'ultimas' => $ultimas,
        ]);
    }

    function muestrasRechazadas(){
        $solicitudes = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area.areaTipoMuestras',
            'userPreanalitica',
            'user'
        ])->where('muestra_rechazada', 'Si')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($solicitudes);
    }
    public function dashboard(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // FILTRO BASE SOBRE SOLICITUDES
        $query = Solicitude::query()
            ->whereNull('deleted_at');

        if ($dateFrom) {
            $query->whereDate('fecha_creacion', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('fecha_creacion', '<=', $dateTo);
        }

        // 1) KPIs de SOLICITUDES
        $totalSolicitudes = (clone $query)->count();

        $totalPacientes = (clone $query)
            ->whereNotNull('paciente_id')
            ->distinct('paciente_id')
            ->count('paciente_id');

        $totalDoctores = (clone $query)
            ->whereNotNull('doctor_id')
            ->distinct('doctor_id')
            ->count('doctor_id');

        $finalizadas = (clone $query)
            ->where('estado', 'FINALIZADO')
            ->count();

        // 2) KPIs de SERVICIOS (TABLA servicio_solicitudes)
        $serviciosQuery = DB::table('servicio_solicitudes as ss')
            ->join('solicitudes as s', 's.id', '=', 'ss.solicitude_id')
            ->whereNull('s.deleted_at');

        if ($dateFrom) {
            $serviciosQuery->whereDate('s.fecha_creacion', '>=', $dateFrom);
        }
        if ($dateTo) {
            $serviciosQuery->whereDate('s.fecha_creacion', '<=', $dateTo);
        }

        $totalServicios = (clone $serviciosQuery)->count(); // filas en servicio_solicitudes

        $promedioServicios = $totalSolicitudes > 0
            ? round($totalServicios / $totalSolicitudes, 1)
            : 0;

        // 3) KPIs de PREANALÍTICA (TABLA solitude_pre_analiticas)
        $preQuery = DB::table('solitude_pre_analiticas as spa')
            ->join('solicitudes as s', 's.id', '=', 'spa.solicitude_id')
            ->whereNull('s.deleted_at');

        if ($dateFrom) {
            $preQuery->whereDate('s.fecha_creacion', '>=', $dateFrom);
        }
        if ($dateTo) {
            $preQuery->whereDate('s.fecha_creacion', '<=', $dateTo);
        }

        $totalMuestrasPre = (clone $preQuery)->count();

        // 4) SOLICITUDES Y SERVICIOS POR ÁREA
        $porArea = DB::table('servicio_solicitudes as ss')
            ->join('areas as a', 'a.id', '=', 'ss.area_id')
            ->join('solicitudes as s', 's.id', '=', 'ss.solicitude_id')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('s.fecha_creacion', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('s.fecha_creacion', '<=', $dateTo);
            })
            ->groupBy('ss.area_id', 'a.name')
            ->select(
                'ss.area_id',
                'a.name as area_nombre',
                DB::raw('COUNT(DISTINCT s.id) as solicitudes'),
                DB::raw('COUNT(ss.id) as servicios')
            )
            ->orderByDesc('solicitudes')
            ->get();

        // 5) TOP SERVICIOS MÁS SOLICITADOS
        $topServicios = DB::table('servicio_solicitudes as ss')
            ->join('servicios as se', 'se.id', '=', 'ss.servicio_id')
            ->join('solicitudes as s', 's.id', '=', 'ss.solicitude_id')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('s.fecha_creacion', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('s.fecha_creacion', '<=', $dateTo);
            })
            ->groupBy('ss.servicio_id', 'se.nombre')
            ->select(
                'ss.servicio_id',
                'se.nombre as servicio_nombre',
                DB::raw('COUNT(*) as total')
            )
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 6) TOP TIPOS DE MUESTRA PREANALÍTICA
        $porTipoMuestra = DB::table('solitude_pre_analiticas as spa')
            ->join('area_tipo_muestras as atm', 'atm.id', '=', 'spa.area_tipo_muestra_id')
            ->join('areas as a', 'a.id', '=', 'atm.area_id')
            ->join('solicitudes as s', 's.id', '=', 'spa.solicitude_id')
            ->whereNull('s.deleted_at')
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('s.fecha_creacion', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('s.fecha_creacion', '<=', $dateTo);
            })
            ->groupBy('atm.id', 'atm.tipo_muestra', 'a.name')
            ->select(
                'atm.id',
                'atm.tipo_muestra',
                'a.name as area_nombre',
                DB::raw('COUNT(*) as total')
            )
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 7) SERIE POR FECHA (SOLICITUDES/DÍA)
        $serieFechas = (clone $query)
            ->select(DB::raw('DATE(fecha_creacion) as fecha'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DATE(fecha_creacion)'))
            ->orderBy('fecha')
            ->get();

        // 8) ÚLTIMAS SOLICITUDES, CON ÁREAS Y Nº SERVICIOS
        $ultimasSolicitudes = (clone $query)
            ->select(
                'solicitudes.id',
                'solicitudes.nro_registro',
                'solicitudes.codigo_solicitud',
                'solicitudes.paciente_nombre',
                'solicitudes.paciente_edad',
                'solicitudes.doctor_nombre',
                'solicitudes.tipo_atencion',
                'solicitudes.estado',
                'solicitudes.fecha_creacion',
                'solicitudes.hora_solicitud',
                'solicitudes.sala',
                'solicitudes.cama',
                DB::raw('(
                    SELECT p.codigo FROM pacientes p
                    WHERE p.id = solicitudes.paciente_id LIMIT 1
                ) as paciente_codigo'),
                DB::raw('(
                    SELECT e.nombre FROM establecimientos e
                    WHERE e.id = solicitudes.establecimiento_id LIMIT 1
                ) as establecimiento_nombre'),
                DB::raw('(
                    SELECT us.nombre FROM unidad_solicitantes us
                    WHERE us.id = solicitudes.unidad_solicitante_id LIMIT 1
                ) as unidad_solicitante'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM servicio_solicitudes ss
                    WHERE ss.solicitude_id = solicitudes.id
                ) as cant_servicios'),
                DB::raw('(
                    SELECT GROUP_CONCAT(DISTINCT a.name SEPARATOR ", ")
                    FROM servicio_solicitudes ss
                    JOIN areas a ON a.id = ss.area_id
                    WHERE ss.solicitude_id = solicitudes.id
                ) as areas'),
                DB::raw('(
                    SELECT GROUP_CONCAT(ss.nombre SEPARATOR ", ")
                    FROM servicio_solicitudes ss
                    WHERE ss.solicitude_id = solicitudes.id
                ) as pruebas'),
                DB::raw('(
                    SELECT COUNT(*)
                    FROM servicio_solicitudes ss
                    WHERE ss.solicitude_id = solicitudes.id AND ss.realizado != "PENDIENTE"
                ) as cant_realizados')
            )
            ->orderByDesc('solicitudes.fecha_creacion')
            ->orderByDesc('solicitudes.id')
            ->limit(20)
            ->get();

        return response()->json([
            'resumen' => [
                'total_solicitudes' => $totalSolicitudes,
                'total_servicios' => $totalServicios,
                'promedio_servicios' => $promedioServicios,
                'total_muestras_preanaliticas' => $totalMuestrasPre,
                // extras por si luego quieres usarlos
                'total_pacientes' => $totalPacientes,
                'total_doctores' => $totalDoctores,
                'finalizadas' => $finalizadas,
            ],
            'por_area' => $porArea,
            'top_servicios' => $topServicios,
            'por_tipo_muestra' => $porTipoMuestra,
            'serie_fechas' => $serieFechas,
            'ultimas' => $ultimasSolicitudes,
        ]);
    }

    // ── Helper compartido para la query de solicitudes del dashboard ──────────
    private function solicitudesDashboardQuery(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo   = $request->get('date_to',   now()->toDateString());

        $q = Solicitude::query()->whereNull('solicitudes.deleted_at');

        if ($dateFrom) $q->whereDate('solicitudes.fecha_creacion', '>=', $dateFrom);
        if ($dateTo)   $q->whereDate('solicitudes.fecha_creacion', '<=', $dateTo);

        // Filtros adicionales opcionales
        if ($request->filled('estado'))       $q->where('solicitudes.estado', $request->get('estado'));
        if ($request->filled('area_id')) {
            $areaId = $request->get('area_id');
            $q->whereExists(function ($sub) use ($areaId) {
                $sub->from('servicio_solicitudes as ss')
                    ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                    ->where('ss.area_id', $areaId)
                    ->whereNull('ss.deleted_at');
            });
        }
        if ($request->filled('area_ids')) {
            $areaIds = array_filter(explode(',', $request->get('area_ids')));
            if (count($areaIds)) {
                $q->whereExists(function ($sub) use ($areaIds) {
                    $sub->from('servicio_solicitudes as ss')
                        ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                        ->whereIn('ss.area_id', $areaIds)
                        ->whereNull('ss.deleted_at');
                });
            }
        }
        if ($request->filled('filtro_resultado')) {
            switch ($request->get('filtro_resultado')) {
                case 'con_resultados':
                    $q->whereExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.realizado', '!=', 'PENDIENTE')
                            ->whereNull('ss.deleted_at');
                    });
                    break;
                case 'sin_resultados':
                    $q->whereNotExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.realizado', '!=', 'PENDIENTE')
                            ->whereNull('ss.deleted_at');
                    });
                    break;
                case 'completos':
                    $q->whereNotExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.realizado', 'PENDIENTE')
                            ->whereNull('ss.deleted_at');
                    });
                    break;
            }
        }
        if ($request->filled('filtro_recogido')) {
            switch ($request->get('filtro_recogido')) {
                case 'pendiente':
                    $q->whereNotExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.realizado', 'REALIZADO')
                            ->whereNull('ss.deleted_at');
                    });
                    break;
                case 'con_resultado':
                    $q->whereExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.realizado', 'REALIZADO')
                            ->whereNull('ss.deleted_at');
                    })->whereNotExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.fue_recogido', true)
                            ->whereNull('ss.deleted_at');
                    });
                    break;
                case 'recogido':
                    $q->whereExists(function ($sub) {
                        $sub->from('servicio_solicitudes as ss')
                            ->whereColumn('ss.solicitude_id', 'solicitudes.id')
                            ->where('ss.fue_recogido', true)
                            ->whereNull('ss.deleted_at');
                    });
                    break;
            }
        }
        if ($request->filled('filtro_valor')) {
            $filtroValor = $request->get('filtro_valor');
            $q->whereExists(function ($sub) use ($filtroValor) {
                $sub->from('resultado_laboratorios as rl')
                    ->join('area_rangos as ar', 'ar.id', '=', 'rl.area_rango_id')
                    ->whereColumn('rl.solicitude_id', 'solicitudes.id')
                    ->whereNotNull('rl.valor_final')
                    ->whereNull('rl.deleted_at');

                if ($filtroValor === 'alto') {
                    $sub->whereNotNull('ar.rango_maximo')
                        ->whereRaw('rl.valor_final > ar.rango_maximo');
                } elseif ($filtroValor === 'bajo') {
                    $sub->whereNotNull('ar.rango_minimo')
                        ->whereRaw('rl.valor_final < ar.rango_minimo');
                } elseif ($filtroValor === 'fuera_rango') {
                    $sub->whereRaw('(
                        (ar.rango_maximo IS NOT NULL AND rl.valor_final > ar.rango_maximo)
                        OR
                        (ar.rango_minimo IS NOT NULL AND rl.valor_final < ar.rango_minimo)
                    )');
                }
            });
        }
        if ($request->filled('tipo_atencion')) $q->where('solicitudes.tipo_atencion', $request->get('tipo_atencion'));
        if ($request->filled('establecimiento_id')) $q->where('solicitudes.establecimiento_id', $request->get('establecimiento_id'));
        if ($request->filled('search')) {
            $s = $request->get('search');
            $q->where(function ($w) use ($s) {
                $w->where('solicitudes.paciente_nombre', 'like', "%{$s}%")
                  ->orWhere('solicitudes.codigo_solicitud', 'like', "%{$s}%")
                  ->orWhere('solicitudes.nro_registro', 'like', "%{$s}%")
                  ->orWhere('solicitudes.doctor_nombre', 'like', "%{$s}%");
            });
        }

        return $q->select(
            'solicitudes.id',
            'solicitudes.nro_registro',
            'solicitudes.codigo',
            'solicitudes.codigo_solicitud',
            'solicitudes.paciente_nombre',
            'solicitudes.paciente_ci',
            'solicitudes.paciente_edad',
            'solicitudes.doctor_nombre',
            'solicitudes.tipo_atencion',
            'solicitudes.estado',
            'solicitudes.fecha_creacion',
            'solicitudes.hora_solicitud',
            'solicitudes.sala',
            'solicitudes.cama',
            DB::raw('(SELECT p.codigo  FROM pacientes p          WHERE p.id  = solicitudes.paciente_id          LIMIT 1) as paciente_codigo'),
            DB::raw('(SELECT e.nombre  FROM establecimientos e   WHERE e.id  = solicitudes.establecimiento_id   LIMIT 1) as establecimiento_nombre'),
            DB::raw('(SELECT us.nombre FROM unidad_solicitantes us WHERE us.id = solicitudes.unidad_solicitante_id LIMIT 1) as unidad_solicitante'),
            DB::raw('(SELECT COUNT(*) FROM servicio_solicitudes ss WHERE ss.solicitude_id = solicitudes.id) as cant_servicios'),
            DB::raw('(SELECT GROUP_CONCAT(DISTINCT a.name SEPARATOR ", ") FROM servicio_solicitudes ss JOIN areas a ON a.id = ss.area_id WHERE ss.solicitude_id = solicitudes.id) as areas'),
            DB::raw('(SELECT GROUP_CONCAT(ss.nombre SEPARATOR ", ") FROM servicio_solicitudes ss WHERE ss.solicitude_id = solicitudes.id) as pruebas'),
            DB::raw('(SELECT COUNT(*) FROM servicio_solicitudes ss WHERE ss.solicitude_id = solicitudes.id AND ss.realizado != "PENDIENTE") as cant_realizados')
        )->orderByDesc('solicitudes.fecha_creacion')->orderByDesc('solicitudes.id');
    }

    // ── Lista paginada ────────────────────────────────────────────────────────
    public function dashboardList(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $perPage = min($perPage, 200);

        $paginator = $this->solicitudesDashboardQuery($request)->paginate($perPage);

        return response()->json($paginator);
    }

    // ── Exportación a Excel ───────────────────────────────────────────────────
    public function dashboardExcel(Request $request)
    {
        $rows = $this->solicitudesDashboardQuery($request)->get();

        $dateFrom = $request->get('date_from', now()->toDateString());
        $dateTo   = $request->get('date_to',   now()->toDateString());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Solicitudes');

        // ── Título ──
        $sheet->mergeCells('A1:T1');
        $sheet->setCellValue('A1', "REPORTE DE SOLICITUDES  |  {$dateFrom} — {$dateTo}");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // ── Cabecera ──
        $headers = [
            'ID', 'Nro Registro', 'Cód. Solicitud', 'Cód. Paciente', 'Paciente', 'Edad',
            'Doctor', 'Tipo Prestación', 'Áreas', 'Pruebas',
            'N° Servicios', 'Realizados', 'Estado',
            'Establecimiento', 'Unidad Solicitante', 'Sala', 'Cama',
            'Fecha', 'Hora',
        ];
        $col = 1;
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 2, $h);
            $col++;
        }
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->freezePane('A3');

        // ── Filas de datos ──
        $row = 3;
        foreach ($rows as $r) {
            $fecha = $r->fecha_creacion ? substr($r->fecha_creacion, 0, 10) : '';
            $sheet->setCellValueByColumnAndRow(1,  $row, $r->id);
            $sheet->setCellValueByColumnAndRow(2,  $row, $r->nro_registro);
            $sheet->setCellValueByColumnAndRow(3,  $row, $r->codigo_solicitud);
            $sheet->setCellValueByColumnAndRow(4,  $row, $r->paciente_codigo);
            $sheet->setCellValueByColumnAndRow(5,  $row, $r->paciente_nombre);
            $sheet->setCellValueByColumnAndRow(6,  $row, $r->paciente_edad);
            $sheet->setCellValueByColumnAndRow(7,  $row, $r->doctor_nombre);
            $sheet->setCellValueByColumnAndRow(8,  $row, $r->tipo_atencion);
            $sheet->setCellValueByColumnAndRow(9,  $row, $r->areas);
            $sheet->setCellValueByColumnAndRow(10, $row, $r->pruebas);
            $sheet->setCellValueByColumnAndRow(11, $row, (int) $r->cant_servicios);
            $sheet->setCellValueByColumnAndRow(12, $row, (int) $r->cant_realizados);
            $sheet->setCellValueByColumnAndRow(13, $row, $r->estado);
            $sheet->setCellValueByColumnAndRow(14, $row, $r->establecimiento_nombre);
            $sheet->setCellValueByColumnAndRow(15, $row, $r->unidad_solicitante);
            $sheet->setCellValueByColumnAndRow(16, $row, $r->sala);
            $sheet->setCellValueByColumnAndRow(17, $row, $r->cama);
            $sheet->setCellValueByColumnAndRow(18, $row, $fecha);
            $sheet->setCellValueByColumnAndRow(19, $row, $r->hora_solicitud);

            // Alternar color de fila
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F8FF']],
                ]);
            }
            $row++;
        }

        // ── Autosize columnas ──
        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $filename = "solicitudes_{$dateFrom}_{$dateTo}.xlsx";
        $path = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    function solicitudesAnalitica(Request $request)
    {
        $filter = $request->input('filter', '');
        $codigo = $request->input('codigo', '');
        $from   = $request->input('from', now()->startOfMonth()->toDateString());
        $to     = $request->input('to',   now()->endOfMonth()->toDateString());
        $perPage = (int) $request->input('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $query = Solicitude::with([
            'paciente', 'doctor', 'servicios.area', 'servicios.tiposMuestra', 'resultados',
            'hematologia',
            'quimicaSanguinea',
            'uroanalisis',
            'parasitologia',
            'papilomaHumano',
            'panelRespiratorio',
            'panelSexual',
            'cultivoAntibiograma',
            ])
            ->whereIn('estado', ['ENVIADO_ANALITICA', 'ANALITICA_ATENDIENDO', 'FINALIZADO','ANALIZADO','MUESTRA RECHAZADA']);

        $user = $request->user();

        // Si NO es administrador, filtrar por los permisos de área analítica del usuario
        if ($user && $user->role !== 'Administrador') {
            $areaPermisoIds = [12, 13, 14, 15, 16, 17, 18];
            $permisosNombres = $user->getAllPermissions()
                ->whereIn('id', $areaPermisoIds)
                ->pluck('name');

            $areaIds = \DB::table('areas')
                ->whereIn('title', $permisosNombres)
                ->pluck('id');

            $query->whereHas('servicios', function ($q) use ($areaIds) {
                $q->whereIn('servicio_solicitudes.area_id', $areaIds);
            });
        }

        if (!empty($filter)) {
            $query->where(function ($q) use ($filter) {
                $q->where('paciente_nombre', 'like', "%$filter%")
                    ->orWhereHas('paciente', function ($q2) use ($filter) {
                        $q2->where('nombre_completo', 'like', "%$filter%")
                            ->orWhere('ci', 'like', "%$filter%");
                    })
                    ->orWhere('establecimiento_salud', 'like', "%$filter%");
            });
        }

        if (!empty($codigo)) {
            $query->where(function ($q) use ($codigo) {
                $q->where('codigo', 'like', "%$codigo%");
            });
        }

        if (!empty($from)) {
            $query->whereDate('fecha_creacion', '>=', $from);
        }
        if (!empty($to)) {
            $query->whereDate('fecha_creacion', '<=', $to);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function paraPresentacion(Request $request)
    {
        $fecha = $request->input('fecha', now()->toDateString());
        $user  = $request->user();

        $query = \App\Models\ServicioSolicitude::with(['solicitud', 'area'])
            ->whereHas('solicitud', function ($q) use ($fecha) {
                $q->whereDate('fecha_creacion', $fecha)
                  ->whereIn('estado', ['ENVIADO_ANALITICA', 'ANALITICA_ATENDIENDO', 'FINALIZADO', 'ANALIZADO']);
            })
            ->where('realizado', '!=', 'PENDIENTE')
            ->whereNull('deleted_at');

        if ($user && $user->role !== 'Administrador' && $user->area_id) {
            $query->where('area_id', $user->area_id);
        }

        $servicios = $query->orderBy('area_id')->orderBy('solicitude_id')->get();

        // Consultar todas las tablas de lab para obtener el último user_presentacion por solicitude_id
        $solicitudeIds = $servicios->pluck('solicitude_id')->unique()->toArray();
        $labModels = [
            \App\Models\Hematologia::class,
            \App\Models\QuimicaSanguinea::class,
            \App\Models\Uroanalisis::class,
            \App\Models\Parasitologia::class,
            \App\Models\PanelRespiratorio::class,
            \App\Models\PanelSexual::class,
            \App\Models\PapilomaHumano::class,
            \App\Models\CultivoAntibiograma::class,
        ];
        $presentaciones = [];
        foreach ($labModels as $model) {
            $model::with('userPresentacion')
                ->whereIn('solicitude_id', $solicitudeIds)
                ->whereNotNull('user_presentacion_id')
                ->get(['solicitude_id', 'user_presentacion_id', 'fecha_presentacion'])
                ->each(function ($r) use (&$presentaciones) {
                    $sid = $r->solicitude_id;
                    if (
                        !isset($presentaciones[$sid]) ||
                        $r->fecha_presentacion > $presentaciones[$sid]['fecha_presentacion']
                    ) {
                        $presentaciones[$sid] = [
                            'user_presentacion'  => $r->userPresentacion?->name,
                            'fecha_presentacion' => $r->fecha_presentacion?->format('d/m/Y H:i'),
                        ];
                    }
                });
        }

        // Mapa area_id → modelo analítico para obtener el ID del registro por solicitud
        $areaModelMap = [
            1 => \App\Models\Hematologia::class,
            2 => \App\Models\QuimicaSanguinea::class,
            3 => \App\Models\Uroanalisis::class,
            4 => \App\Models\Parasitologia::class,
            5 => \App\Models\CultivoAntibiograma::class,
        ];
        // [area_id][solicitude_id] => analisis_id
        $analisisIds = [];
        $areaIdsPresentes = $servicios->pluck('area_id')->unique()->toArray();
        foreach ($areaIdsPresentes as $areaId) {
            if (!isset($areaModelMap[$areaId])) continue;
            $modelClass = $areaModelMap[$areaId];
            $modelClass::whereIn('solicitude_id', $solicitudeIds)
                ->get(['id', 'solicitude_id'])
                ->each(function ($r) use (&$analisisIds, $areaId) {
                    $analisisIds[$areaId][$r->solicitude_id] = $r->id;
                });
        }
        // Para biología molecular (area_id=7) usar cualquier registro disponible
        $bioMolModels = [
            \App\Models\PanelRespiratorio::class,
            \App\Models\PanelSexual::class,
            \App\Models\PapilomaHumano::class,
        ];
        foreach ($bioMolModels as $modelClass) {
            $modelClass::whereIn('solicitude_id', $solicitudeIds)
                ->get(['id', 'solicitude_id'])
                ->each(function ($r) use (&$analisisIds) {
                    if (!isset($analisisIds[7][$r->solicitude_id])) {
                        $analisisIds[7][$r->solicitude_id] = $r->id;
                    }
                });
        }

        $grouped = $servicios->groupBy('area_id')->map(function ($items) use ($presentaciones, $analisisIds) {
            $areaId = $items->first()->area_id;
            $porSolicitud = $items->groupBy('solicitude_id')->map(function ($ss) use ($presentaciones, $analisisIds, $areaId) {
                $sol = $ss->first()->solicitud;
                $pres = $presentaciones[$sol->id] ?? null;
                return [
                    'solicitud_id'       => $sol->id,
                    'analisis_id'        => $analisisIds[$areaId][$sol->id] ?? null,
                    'paciente_nombre'    => $sol->paciente_nombre,
                    'nro_registro'       => $sol->nro_registro,
                    'user_presentacion'  => $pres['user_presentacion'] ?? null,
                    'fecha_presentacion' => $pres['fecha_presentacion'] ?? null,
                    'servicios'          => $ss->map(fn ($s) => [
                        'id'     => $s->id,
                        'nombre' => $s->nombre,
                    ])->values(),
                ];
            })->values();

            return [
                'area_id'     => $areaId,
                'area_nombre' => $items->first()->area?->name ?? 'Sin área',
                'solicitudes' => $porSolicitud,
            ];
        })->values();

        return response()->json($grouped);
    }

    public function registrarPresentacion(Request $request)
    {
        $solicitudeIds = $request->input('solicitude_ids', []);
        $user          = $request->user();
        $campos        = ['user_presentacion_id' => $user->id, 'fecha_presentacion' => now()];

        foreach ([
            \App\Models\Hematologia::class,
            \App\Models\QuimicaSanguinea::class,
            \App\Models\Uroanalisis::class,
            \App\Models\Parasitologia::class,
            \App\Models\PanelRespiratorio::class,
            \App\Models\PanelSexual::class,
            \App\Models\PapilomaHumano::class,
            \App\Models\CultivoAntibiograma::class,
        ] as $model) {
            $model::whereIn('solicitude_id', $solicitudeIds)->update($campos);
        }

        return response()->json(['ok' => true]);
    }

    public function pdfPresentacion(Request $request)
    {
        $fecha  = $request->input('fecha', now()->toDateString());
        $areaId = $request->input('area_id');

        $query = \App\Models\ServicioSolicitude::with(['solicitud', 'area'])
            ->whereHas('solicitud', function ($q) use ($fecha) {
                $q->whereDate('fecha_creacion', $fecha)
                  ->whereIn('estado', ['ENVIADO_ANALITICA', 'ANALITICA_ATENDIENDO', 'FINALIZADO', 'ANALIZADO']);
            })
            ->where('realizado', '!=', 'PENDIENTE')
            ->whereNull('deleted_at');

        if ($areaId) {
            $query->where('area_id', $areaId);
        }

        $servicios = $query->orderBy('area_id')->orderBy('solicitude_id')->get();

        $solicitudeIds = $servicios->pluck('solicitude_id')->unique()->toArray();
        $areaModelMap = [
            1 => \App\Models\Hematologia::class,
            2 => \App\Models\QuimicaSanguinea::class,
            3 => \App\Models\Uroanalisis::class,
            4 => \App\Models\Parasitologia::class,
            5 => \App\Models\CultivoAntibiograma::class,
        ];
        $analisisIds = [];
        foreach ($servicios->pluck('area_id')->unique()->toArray() as $aid) {
            if (!isset($areaModelMap[$aid])) continue;
            $areaModelMap[$aid]::whereIn('solicitude_id', $solicitudeIds)
                ->get(['id', 'solicitude_id'])
                ->each(function ($r) use (&$analisisIds, $aid) {
                    $analisisIds[$aid][$r->solicitude_id] = $r->id;
                });
        }
        foreach ([\App\Models\PanelRespiratorio::class, \App\Models\PanelSexual::class, \App\Models\PapilomaHumano::class] as $m) {
            $m::whereIn('solicitude_id', $solicitudeIds)->get(['id', 'solicitude_id'])
                ->each(function ($r) use (&$analisisIds) {
                    if (!isset($analisisIds[7][$r->solicitude_id])) {
                        $analisisIds[7][$r->solicitude_id] = $r->id;
                    }
                });
        }

        $grupos = $servicios->groupBy('area_id')->map(function ($items) use ($analisisIds) {
            $areaId = $items->first()->area_id;
            $solicitudes = $items->groupBy('solicitude_id')->map(function ($ss) use ($analisisIds, $areaId) {
                $sol = $ss->first()->solicitud;
                return [
                    'solicitud_id'    => $sol->id,
                    'analisis_id'     => $analisisIds[$areaId][$sol->id] ?? null,
                    'paciente_nombre' => $sol->paciente_nombre,
                    'nro_registro'    => $sol->nro_registro,
                    'servicios'       => $ss->map(fn ($s) => [
                        'id'     => $s->id,
                        'nombre' => $s->nombre,
                    ])->values(),
                ];
            })->values();

            return [
                'area_id'     => $areaId,
                'area_nombre' => $items->first()->area?->name ?? 'Sin área',
                'solicitudes' => $solicitudes,
            ];
        })->values();

        $areaNombre = $areaId ? ($grupos->first()['area_nombre'] ?? '') : '';

        $pdf = Pdf::loadView('pdf.registro_presentacion', compact('grupos', 'fecha', 'areaNombre'))
            ->setPaper('legal', 'landscape');

        return $pdf->stream("registro_entrega_{$fecha}.pdf");
    }

    public function imprimirAnaliticaPublica($codigo)
    {
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'resultados',
            'servicios.area',
            'servicios.tiposMuestra',
        ])->where('nro_registro', $codigo)->firstOrFail();

        $pdf = $this->buildPdfFromSolicitud($solicitud);

        return $pdf->stream('LAB_' . $solicitud->nro_registro . '.pdf');
    }

    public function imprimirAnalitica($id)
    {
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'resultados',
            'servicios.area',
            'servicios.tiposMuestra',
            'userAnalitica'
        ])->findOrFail($id);

        $pdf = $this->buildPdfFromSolicitud($solicitud);

        return $pdf->stream('LAB_' . $solicitud->nro_registro . '.pdf');
    }

    protected function buildPdfFromSolicitud(Solicitude $solicitud)
    {
        $perfilHemograma = PerfilImpresion::where('codigo', 'HEMOGRAMA')
            ->with(['items.areaRango'])
            ->first();

        $perfilQuimica = PerfilImpresion::where('codigo', 'QUIMICA')
            ->with(['items.areaRango'])
            ->first();

        $hemoItems = $perfilHemograma
            ? $perfilHemograma->items->sortBy(['columna', 'seccion', 'orden'])
            : collect();

        $quimicaItems = $perfilQuimica
            ? $perfilQuimica->items->sortBy(['columna', 'seccion', 'orden'])
            : collect();

        $resultados = $solicitud->resultados;

        $pdf = Pdf::loadView('reportes.solicitud_media_carta', [
            'solicitud' => $solicitud,
            'hemoItems' => $hemoItems,
            'quimicaItems' => $quimicaItems,
            'perfilHemograma' => $perfilHemograma,
            'perfilQuimica' => $perfilQuimica,
            'resultados' => $resultados,
        ])->setPaper('letter', 'portrait');

        return $pdf;
    }

    public function generarCodigo(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);

        if ($request->filled('tipo_atencion')) {
            $solicitud->tipo_atencion = $request->input('tipo_atencion');
        } elseif (empty($solicitud->tipo_atencion)) {
            $solicitud->tipo_atencion = 'SI';
        }

        if (empty($solicitud->fecha_creacion)) {
            $solicitud->fecha_creacion = now()->toDateString();
        }

        $paciente = Paciente::find($solicitud->paciente_id);
//        $nombreCompleto = $paciente ? $paciente->nombre_completo : 'Desconocido';
//        $nombreCompleto = $solicitud->paciente_nombre ?? ($paciente ? $paciente->nombre_completo : 'Desconocido');
//        $nro_registro = $this->nroRegistro($nombreCompleto, $paciente->fecha_nac ?? null);
//
//        $solicitud->codigo = $this->generarCodigoPorTipoYMes($solicitud);
//        $solicitud->nro_registro = $nro_registro;
        $solicitud->estado = 'ATENDIENDO';
        $solicitud->fecha_pre_analitica = now();
        $solicitud->user_preanalitica_id = $request->user() ? $request->user()->id : null;
        $solicitud->iniciales = $solicitud->user && $solicitud->user->establecimiento ? $solicitud->user->establecimiento->inicial : '';

        $solicitud->save();

        return response()->json($solicitud->fresh(['paciente', 'doctor', 'servicios.tiposMuestra']));
    }

    protected function generarCodigoPorTipoYMes(Solicitude $solicitud): int
    {
        $tipo = $solicitud->tipo_atencion ?? 'SI';

        $fechaBase = $solicitud->fecha_creacion ?: now()->toDateString();
        $timestamp = strtotime($fechaBase);

        $anio = date('Y', $timestamp);
        $mes  = date('m', $timestamp);

        $establecimientoId = $solicitud->establecimiento_origen_id
            ?? ($solicitud->user && $solicitud->user->establecimiento ? $solicitud->user->establecimiento->id : null);

        $q = Solicitude::query()
            ->where('tipo_atencion', $tipo)
            ->whereYear('fecha_creacion', $anio)
            ->whereMonth('fecha_creacion', $mes)
            ->whereNotNull('codigo');

        if ($establecimientoId) {
            $q->where('establecimiento_origen_id', $establecimientoId);
        }

        // si para EXTERNO quieres reiniciar por día
        if ($tipo !== 'SI') {
            $q->whereDate('fecha_creacion', date('Y-m-d', $timestamp));
        }

        $ultimoCodigo = $q->max('codigo');

        return $ultimoCodigo ? ((int)$ultimoCodigo + 1) : 1;
    }

    public function guardarPreAnalitica(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);
        $area_tipo_muestras = $request->input('area_tipo_muestras', []);

//        $urlSocket = env('URL_SOCKET_IO', null);
//        //return response()->json(['message' => 'URL_SOCKET_IO no está configurada', 'url' => $urlSocket], 500);
//        $response = Http::get($urlSocket . '/silSolicitud');


        foreach ($area_tipo_muestras as $area) {
            if (isset($area['area_tipo_muestras']) && is_array($area['area_tipo_muestras'])) {
                foreach ($area['area_tipo_muestras'] as $tipoMuestra) {
                    $idTipo = $tipoMuestra['id'];
                    $existing = SolitudePreAnalitica::where('solicitude_id', $solicitud->id)
                        ->where('area_tipo_muestra_id', $idTipo)
                        ->first();

                    if ($existing) {
                        $existing->selected = !empty($tipoMuestra['selected']);
                        $existing->save();
                    } else {
                        $findArea = \App\Models\AreaTipoMuestra::find($idTipo);
                        $SolitudePreAnalitica = new SolitudePreAnalitica();
                        $SolitudePreAnalitica->solicitude_id = $solicitud->id;
                        $SolitudePreAnalitica->area_tipo_muestra_id = $idTipo;
                        $SolitudePreAnalitica->estado = 'Pendiente';
                        $SolitudePreAnalitica->nombre = $findArea ? $findArea->tipo_muestra : '';
                        $SolitudePreAnalitica->selected = !empty($tipoMuestra['selected']) ? true : false;
                        $SolitudePreAnalitica->save();
                    }
                }
            }
        }

        if (empty($solicitud->fecha_envio_analitica)) {
            $solicitud->fecha_envio_analitica = now();
        }

        if (in_array($solicitud->estado, ['CREADO', 'ATENDIENDO'], true)) {
            $solicitud->estado = 'ENVIADO_ANALITICA';
        }
//        'muestra_rechazada',
//        'muestra_observacion',
        $solicitud->muestra_rechazada = 'No';
        $solicitud->muestra_observacion = null;
        $solicitud->user_preanalitica_id = $request->user() ? $request->user()->id : null;
        $solicitud->save();

        return response()->json([
            'message' => 'Muestras preanalíticas actualizadas',
            'area_tipo_muestras' => $solicitud->load('preAnaliticaComentarios.user'),
        ]);
    }
    public function storePreAnaliticaComentario(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);

        $data = $request->validate([
            'comentario' => 'required|string|max:3000',
        ]);

        $comentario = SolicitudePreAnaliticaComentario::create([
            'solicitude_id' => $solicitud->id,
            'user_id' => $request->user()->id,
            'comentario' => trim($data['comentario']),
        ])->load('user');

        return response()->json($comentario, 201);
    }
    public function destroyPreAnaliticaComentario(Request $request, $id, $comentarioId)
    {
        $comentario = SolicitudePreAnaliticaComentario::where('solicitude_id', $id)
            ->findOrFail($comentarioId);

        if ((int) $comentario->user_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Solo puede eliminar sus propios comentarios.'
            ], 403);
        }

        $comentario->delete();

        return response()->json([
            'message' => 'Comentario eliminado correctamente.'
        ]);
    }
    public function solicitudesAreaPreanaliticaEstado(Request $request)
    {
        $from    = $request->query('from') ?: now()->startOfMonth()->toDateString();
        $to      = $request->query('to')   ?: now()->endOfMonth()->toDateString();
        $filter  = trim((string) $request->query('filter', ''));
        $codigo  = trim((string) $request->query('codigo', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 300));

        $query = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area.rangos',
            'servicios.tiposMuestra',
            'preAnaliticaMuestras.areaTipoMuestra.area',
            'preAnaliticaComentarios.user',
            'userPreanalitica',
            'userAnalitica',
            'user',
        ])
            ->whereDate('fecha_creacion', '>=', $from)
            ->whereDate('fecha_creacion', '<=', $to)
            ->whereIn('estado', ['ATENDIENDO','MUESTRA RECHAZADA','ENVIADO_ANALITICA','ANALIZADO','MUESTRA NO TOMADA']);


        $query->orderByRaw("FIELD(estado, 'ATENDIENDO', 'ENVIADO_ANALITICA', 'ANALIZADO', 'MUESTRA RECHAZADA', 'MUESTRA NO TOMADA') ASC");

        if (!empty($filter)) {
            $query->whereHas('paciente', function ($q) use ($filter) {
                $q->where('nombre_completo', 'like', "%$filter%")
                    ->orWhere('ci', 'like', "%$filter%");
            });
        }

        if (!empty($codigo)) {
            $query->where(function ($q) use ($codigo) {
                $q->where('codigo', 'like', "%$codigo%");
            });
        }

        $query->orderByRaw("FIELD(estado, 'ATENDIENDO', 'ENVIADO_ANALITICA', 'ANALIZADO', 'MUESTRA RECHAZADA', 'MUESTRA NO TOMADA') ASC")
            ->orderByDesc('id');

        return $query->paginate($perPage);
    }

    public function solicitudesAreaPreanalitica(Request $request)
    {
        $filter  = $request->input('filter', '');
        $codigo  = $request->input('codigo', '');
        $from    = $request->input('from', now()->startOfMonth()->toDateString());
        $to      = $request->input('to',   now()->endOfMonth()->toDateString());

        $query = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
            'servicios.tiposMuestra',
            'preAnaliticaMuestras.areaTipoMuestra',
            'preAnaliticaComentarios.user',
            'userPreanalitica',
            'user',
            'solicitudRechazadas.user',
            'solicitudRechazadas.area',
        ])
            ->whereDate('fecha_creacion', '>=', $from)
            ->whereDate('fecha_creacion', '<=', $to)
            ->whereIn('estado', ['CREADO', 'ATENDIENDO','MUESTRA RECHAZADA']);

        if (!empty($filter)) {
            $query->whereHas('paciente', function ($q) use ($filter) {
                $q->where('nombre_completo', 'like', "%$filter%")
                    ->orWhere('ci', 'like', "%$filter%");
            });
        }

        if (!empty($codigo)) {
            $query->where(function ($q) use ($codigo) {
                $q->where('codigo', 'like', "%$codigo%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $solicitudes = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($solicitudes);
    }

    function nroRegistro($nombreCompleto, $fechaNac)
    {
        $nombreCompleto = trim((string)$nombreCompleto);

        if ($nombreCompleto === '' || empty($fechaNac)) {
            return null;
        }

        $partes = preg_split('/\s+/', mb_strtoupper($nombreCompleto, 'UTF-8'));

        if (count($partes) >= 3) {
            $nombre = $partes[0];
            $apPat = $partes[count($partes) - 2];
            $apMat = $partes[count($partes) - 1];
        } elseif (count($partes) === 2) {
            $nombre = $partes[0];
            $apPat = $partes[1];
            $apMat = $partes[1];
        } else {
            $nombre = $partes[0];
            $apPat = $partes[0];
            $apMat = $partes[0];
        }

        $iniciales =
            mb_substr($nombre, 0, 1, 'UTF-8') .
            mb_substr($apPat, 0, 1, 'UTF-8') .
            mb_substr($apMat, 0, 1, 'UTF-8');

        $timestamp = strtotime($fechaNac);
        if ($timestamp === false) {
            return $iniciales;
        }

        $fechaFormateada = date('dmy', $timestamp);

        return $iniciales . $fechaFormateada;
    }

    // ── Exportar lista de solicitudes a Excel ────────────────────────────────
    public function indexExcel(Request $request)
    {
        $query = Solicitude::with(['servicios']);

        if ($request->filled('from')) {
            $query->whereDate('fecha_creacion', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('fecha_creacion', '<=', $request->to);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo_atencion')) {
            $query->where('tipo_atencion', $request->tipo_atencion);
        }

        $rows    = $query->orderBy('id', 'desc')->get();
        $from    = $request->get('from', now()->toDateString());
        $to      = $request->get('to',   now()->toDateString());

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Solicitudes');

        // ── Título ──
        $sheet->mergeCells('A1:K1');
        $sheet->setCellValue('A1', "LISTADO DE SOLICITUDES  |  {$from} — {$to}");
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // ── Cabecera ──
        $headers = [
            'N°', 'Nro Registro', 'Cód. Solicitud', 'Fecha', 'Paciente',
            'Doctor', 'Tipo Atención', 'Servicios', 'Estado', 'Consentimiento', 'Observación',
        ];
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 2, $h);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->freezePane('A3');

        // ── Filas ──
        $rowNum = 3;
        $n = 1;
        foreach ($rows as $r) {
            $fecha    = $r->fecha_creacion ? substr($r->fecha_creacion, 0, 10) : '';
            $servicios = $r->servicios->pluck('nombre')->implode(', ');
            $bgColor  = ($rowNum % 2 === 0) ? 'FFF0F4FF' : 'FFFFFFFF';

            $sheet->setCellValueByColumnAndRow(1,  $rowNum, $n++);
            $sheet->setCellValueByColumnAndRow(2,  $rowNum, $r->nro_registro ?? '');
            $sheet->setCellValueByColumnAndRow(3,  $rowNum, $r->codigo_solicitud ?? $r->codigo ?? '');
            $sheet->setCellValueByColumnAndRow(4,  $rowNum, $fecha);
            $sheet->setCellValueByColumnAndRow(5,  $rowNum, $r->paciente_nombre ?? '');
            $sheet->setCellValueByColumnAndRow(6,  $rowNum, $r->doctor_nombre ?? '');
            $sheet->setCellValueByColumnAndRow(7,  $rowNum, $r->tipo_atencion ?? '');
            $sheet->setCellValueByColumnAndRow(8,  $rowNum, $servicios);
            $sheet->setCellValueByColumnAndRow(9,  $rowNum, $r->estado ?? '');
            $sheet->setCellValueByColumnAndRow(10, $rowNum, $r->muestra_rechazada ? 'RECHAZADA' : '');
            $sheet->setCellValueByColumnAndRow(11, $rowNum, $r->muestra_observacion ?? '');

            $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $rowNum++;
        }

        // ── Fila totales ──
        $sheet->setCellValueByColumnAndRow(1, $rowNum, 'TOTAL');
        $sheet->setCellValueByColumnAndRow(2, $rowNum, count($rows));
        $sheet->getStyle("A{$rowNum}:{$lastCol}{$rowNum}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE3F0FF']],
        ]);

        // ── Autosize ──
        foreach (range(1, count($headers)) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }

        $filename = "solicitudes_{$from}_{$to}.xlsx";
        $path = storage_path("app/{$filename}");
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    // ── Exportar lista de solicitudes a PDF ──────────────────────────────────
    public function indexPdf(Request $request)
    {
        $query = Solicitude::with(['servicios']);

        if ($request->filled('from')) {
            $query->whereDate('fecha_creacion', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('fecha_creacion', '<=', $request->to);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo_atencion')) {
            $query->where('tipo_atencion', $request->tipo_atencion);
        }

        $rows      = $query->orderBy('id', 'desc')->get();
        $from      = $request->get('from', now()->toDateString());
        $to        = $request->get('to',   now()->toDateString());
        $generado  = now();

        $pdf = Pdf::loadView('reportes.solicitudes_lista', compact('rows', 'from', 'to', 'generado'))
            ->setPaper('letter', 'landscape');

        return $pdf->download("solicitudes_{$from}_{$to}.pdf");
    }

    public function index(Request $request)
    {
        $query = Solicitude::with(['paciente', 'doctor', 'servicios.tiposMuestra', 'consentimiento']);

        if ($request->filled('from')) {
            $query->whereDate('fecha_creacion', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('fecha_creacion', '<=', $request->to);
        }
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tipo_atencion')) {
            $query->where('tipo_atencion', $request->tipo_atencion);
        }
        if ($request->filled('codigo')) {
            $c = $request->codigo;
            $query->where(function ($q) use ($c) {
                $q->where('codigo', 'like', "%$c%");
            });
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('paciente_nombre', 'like', "%$s%")
                  ->orWhere('codigo_solicitud', 'like', "%$s%")
                  ->orWhere('nro_registro', 'like', "%$s%")
                  ->orWhere('doctor_nombre', 'like', "%$s%");
            });
        }

        $perPage = min((int) $request->get('per_page', 25), 200);

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function show($id)
    {
        return Solicitude::with(['paciente', 'doctor', 'servicios.tiposMuestra', 'consentimiento', 'unidadSolicitante'])->findOrFail($id);
    }

    public function store(Request $request)
    {
        $servicios = $request->input('servicios', []);
        if (empty($servicios) || !is_array($servicios)) {
            return response()->json(['message' => 'Debe seleccionar al menos un servicio'], 422);
        }

        $data = $request->all();

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        $this->resolverUnidadSolicitante($data, $request);

//        error_log('establecimiento_salud: ' . $request->establecimiento_salud);
        $establecimientoSalud = \App\Models\Establecimiento::where('nombre', $request->establecimiento_salud)->first();
//        error_log('EstablecimientoSalud: ' . json_encode($establecimientoSalud));
        if ($establecimientoSalud) {
//            error_log('entro');
            $data['establecimiento_id'] = $establecimientoSalud->id;
        }else{
            error_log('no entro');
        }

        $ci = $request->paciente_ci;
        $paciente = $this->pacienteUpsert($ci, $data);
        if ($paciente) {
            $data['paciente_id'] = $paciente->id;
        }

        $data['fecha_creacion'] = now();
        $data['establecimiento_origen_id'] = $request->user() && $request->user()->establecimiento ? $request->user()->establecimiento->id : null;

        if ($request->filled('doctor_id')) {
            $d = Doctor::find($request->doctor_id);
            if ($d) {
                $data['doctor_nombre'] = $data['doctor_nombre'] ?? $d->nombre;
                $data['doctor_especialidad'] = $data['doctor_especialidad'] ?? $d->especialidad;
                $data['doctor_ci'] = $data['doctor_ci'] ?? $d->ci;
                $data['doctor_telefono'] = $data['doctor_telefono'] ?? $d->telefono;
                $data['doctor_email'] = $data['doctor_email'] ?? $d->email;
                $data['doctor_registro'] = $data['doctor_registro'] ?? $d->registro;
            }
        }


        $solicitud = Solicitude::create($data);
        $nombreCompleto = $solicitud->paciente_nombre ?? ($paciente ? $paciente->nombre_completo : 'Desconocido');
        $nro_registro = $this->nroRegistro($nombreCompleto, $paciente->fecha_nac ?? null);

        $solicitud->codigo = $request->filled('codigo')
            ? $request->input('codigo')
            : $this->generarCodigoPorTipoYMes($solicitud);
        $solicitud->nro_registro = $request->filled('nro_registro')
            ? $request->input('nro_registro')
            : $nro_registro;
        $solicitud->codigo_solicitud = ($solicitud->nro_registro ?? '') . ($solicitud->codigo ?? '');
        $solicitud->save();

//        $this->syncServicios($solicitud, $request->input('servicios', []));
        $servicios = $request->input('servicios', []);
        foreach ($servicios as $servicio) {
//            $servicioSolicitud = Servicio::find($servicio['id']);
            $newServicioSolicitud = new ServicioSolicitude();
            $newServicioSolicitud->solicitude_id = $solicitud->id;
            $newServicioSolicitud->servicio_id = $servicio['id'];
            $newServicioSolicitud->area_id = $servicio['area_id'];
            $newServicioSolicitud->precio = $servicio['precio'] ?? null;
            $newServicioSolicitud->nombre = $servicio['nombre'] ?? '';
            $newServicioSolicitud->save();
        }
//        $urlSocket = env('URL_SOCKET_IO', null);
        $urlSocket ='https://saventura.tuprogam.com/';
//        //return response()->json(['message' => 'URL_SOCKET_IO no está configurada', 'url' => $urlSocket], 500);
        $response = Http::get($urlSocket . '/silSolicitud');

        return response()->json($solicitud->load(['paciente', 'doctor', 'servicios.tiposMuestra', 'consentimiento']), 201);
    }

    protected function pacienteUpsert($ci, &$data)
    {
        if (empty($ci) || $ci === '') {
            $p = Paciente::create([
                'nombre_completo' => $data['paciente_nombre'],
                'ci' => $ci,
                'codigo' => Paciente::generarCodigo($data['paciente_nombre'], $data['paciente_fecha_nac'] ?? null),
                'telefono' => $data['paciente_telefono'] ?? null,
                'direccion' => $data['paciente_direccion'] ?? null,
                'fecha_nac' => $data['paciente_fecha_nac'] ?? null,
                'genero' => $data['paciente_genero'] ?? null,
                'edad' => $data['paciente_edad'] ?? null,
                'discapacidad' => $data['paciente_discapacidad'] ?? 0,
                'discapacidad_cual' => $data['paciente_discapacidad_cual'] ?? null,
                'discapacidad_otro' => $data['paciente_discapacidad_otro'] ?? null,
                'embarazo' => $data['paciente_embarazo'] ?? 0,
                'fum' => $data['paciente_fum'] ?? null,
                'sem_gest' => $data['paciente_sem_gest'] ?? null,
            ]);
            $data['paciente_id'] = $p->id;
            return $p;
        }

        $p = Paciente::where('ci', $ci)->first();
        if ($p) {
            $p->nombre_completo = $data['paciente_nombre'] ?? $p->nombre_completo;
            $p->telefono = $data['paciente_telefono'] ?? $p->telefono;
            $p->direccion = $data['paciente_direccion'] ?? $p->direccion;
            $p->fecha_nac = $data['paciente_fecha_nac'] ?? $p->fecha_nac;
            $p->genero = $data['paciente_genero'] ?? $p->genero;
            $p->edad = $data['paciente_edad'] ?? $p->edad;
            $p->discapacidad = $data['paciente_discapacidad'] ?? $p->discapacidad;
            $p->discapacidad_cual = $data['paciente_discapacidad_cual'] ?? $p->discapacidad_cual;
            $p->discapacidad_otro = $data['paciente_discapacidad_otro'] ?? $p->discapacidad_otro;
            $p->embarazo = $data['paciente_embarazo'] ?? $p->embarazo;
            $p->fum = $data['paciente_fum'] ?? $p->fum;
            $p->sem_gest = $data['paciente_sem_gest'] ?? $p->sem_gest;
            $p->codigo = Paciente::generarCodigo($p->nombre_completo, $p->fecha_nac);
            $p->save();
        } else {
            $p = Paciente::create([
                'nombre_completo' => $data['paciente_nombre'],
                'ci' => $ci,
                'codigo' => Paciente::generarCodigo($data['paciente_nombre'], $data['paciente_fecha_nac'] ?? null),
                'telefono' => $data['paciente_telefono'] ?? null,
                'direccion' => $data['paciente_direccion'] ?? null,
                'fecha_nac' => $data['paciente_fecha_nac'] ?? null,
                'genero' => $data['paciente_genero'] ?? null,
                'edad' => $data['paciente_edad'] ?? null,
                'discapacidad' => $data['paciente_discapacidad'] ?? 0,
                'discapacidad_cual' => $data['paciente_discapacidad_cual'] ?? null,
                'discapacidad_otro' => $data['paciente_discapacidad_otro'] ?? null,
                'embarazo' => $data['paciente_embarazo'] ?? 0,
                'fum' => $data['paciente_fum'] ?? null,
                'sem_gest' => $data['paciente_sem_gest'] ?? null,
            ]);
        }

        $data['paciente_id'] = $p->id;
        return $p;
    }

    public function update(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);
//        if (!empty($solicitud->codigo)) {
//            return response()->json(['message' => 'No se puede modificar una solicitud que ya tiene código asignado'], 422);
//        }
        $data = $request->all();
        $this->resolverUnidadSolicitante($data, $request);

        $ci = $request->paciente_ci;
        if (!empty($ci)) {
            $paciente = $this->pacienteUpsert($ci, $data);
            if ($paciente) {
                $data['paciente_id'] = $paciente->id;
            }
        }

        if ($request->filled('doctor_id')) {
            $d = Doctor::find($request->doctor_id);
            if ($d) {
                $data['doctor_nombre'] = $data['doctor_nombre'] ?? $d->nombre;
                $data['doctor_especialidad'] = $data['doctor_especialidad'] ?? $d->especialidad;
                $data['doctor_ci'] = $data['doctor_ci'] ?? $d->ci;
                $data['doctor_telefono'] = $data['doctor_telefono'] ?? $d->telefono;
                $data['doctor_email'] = $data['doctor_email'] ?? $d->email;
                $data['doctor_registro'] = $data['doctor_registro'] ?? $d->registro;
            }
        }

        $servicios = $request->input('servicios', []);
//        delted servicos antguos
        ServicioSolicitude::where('solicitude_id', $solicitud->id)->delete();
        foreach ($servicios as $servicio) {
//            $servicioSolicitud = Servicio::find($servicio['id']);
            $newServicioSolicitud = new ServicioSolicitude();
            $newServicioSolicitud->solicitude_id = $solicitud->id;
            $newServicioSolicitud->servicio_id = $servicio['id'];
            $newServicioSolicitud->area_id = $servicio['area_id'];
            $newServicioSolicitud->precio = $servicio['precio'] ?? null;
            $newServicioSolicitud->nombre = $servicio['nombre'] ?? '';
            $newServicioSolicitud->save();
        }

        $this->syncServicios($solicitud, $request->input('servicios', []));
//        solitud uupte
        $solicitud->update($data);

        return response()->json($solicitud->load(['paciente', 'doctor', 'servicios.tiposMuestra', 'consentimiento', 'unidadSolicitante']));
    }

    protected function resolverUnidadSolicitante(array &$data, Request $request): void
    {
        $unidadId = $request->input('unidad_solicitante_id');

        if ($unidadId) {
            $unidad = UnidadSolicitante::find($unidadId);

            if ($unidad) {
                $data['unidad_solicitante_id'] = $unidad->id;
                $data['sala'] = $unidad->nombre;
                return;
            }
        }

        $sala = trim((string) ($request->input('sala') ?? ''));

        if ($sala === '') {
            $data['unidad_solicitante_id'] = null;
            $data['sala'] = null;
            return;
        }

        $unidad = UnidadSolicitante::firstOrCreate(['nombre' => $sala]);
        $data['unidad_solicitante_id'] = $unidad->id;
        $data['sala'] = $unidad->nombre;
    }

    protected function syncServicios(Solicitude $solicitud, array $servicios)
    {
        $pivotData = [];

        foreach ($servicios as $serv) {
            if (!isset($serv['id'])) {
                continue;
            }
            $pivotData[$serv['id']] = [
                'precio' => $serv['precio'] ?? null,
            ];
        }

        $solicitud->servicios()->sync($pivotData);
    }

    public function destroy($id)
    {
        $solicitud = Solicitude::findOrFail($id);
        $solicitud->delete();

        return response()->json(['message' => 'Solicitud eliminada correctamente']);
    }

    public function solicitudesAreaAnalitica(Request $request)
    {
        $filter = $request->input('filter', '');

        $query = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area.rangos',
            'servicios.tiposMuestra',
            'preAnaliticaMuestras.areaTipoMuestra.area',
            'userPreanalitica',
            'userAnalitica',
            'user',
        ])
            ->whereIn('estado', ['ENVIADO_ANALITICA', 'ANALITICA_ATENDIENDO', 'FINALIZADO']);

        if (!empty($filter)) {
            $query->where(function ($q) use ($filter) {
                $q->where('paciente_nombre', 'like', "%$filter%")
                    ->orWhereHas('paciente', function ($q2) use ($filter) {
                        $q2->where('nombre_completo', 'like', "%$filter%")
                            ->orWhere('ci', 'like', "%$filter%");
                    })
                    ->orWhere('establecimiento_salud', 'like', "%$filter%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $solicitudes = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json($solicitudes);
    }

    public function showAnalitica($id)
    {
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area.rangos',
            'servicios.tiposMuestra',
            'resultados',
            'preAnaliticaMuestras.areaTipoMuestra.area',
            'propiedades',
            'userPreanalitica',
            'solicitudeFormularios'
        ])->findOrFail($id);

        return response()->json($solicitud);
    }

    protected function parseValorNullable($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
            if ($value === '') {
                return null;
            }
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float)$value;
    }

    public function guardarAnalitica(Request $request, $id)
    {
        $solicitud = Solicitude::findOrFail($id);
        $resultados = $request->input('resultados', []);
        $propiedadesArea = $request->input('propiedades_area', []); // <-- NUEVO
//        formularios: this.solicitud.solicitude_formularios || []
        $fomularios = $request->input('formularios', []);

        DB::transaction(function () use ($solicitud, $resultados, $propiedadesArea, $fomularios) {
//            SolicitudeFormulario deletes
            SolicitudeFormulario::where('solicitude_id', $solicitud->id)->delete();
            foreach ($fomularios as $formularioData) {
                $solicitudeFormulario = SolicitudeFormulario::updateOrCreate(
                    [
                        'solicitude_id' => $solicitud->id,
                        'formulario_id' => $formularioData['formulario_id'],
                        'area_id' => $formularioData['area_id'] ?? null,
                    ],
                    [
                        'nombre' => $formularioData['nombre'] ?? null,
                        'html' => $formularioData['html'] ?? null,
                    ]
                );
            }

            foreach ($resultados as $areaId => $rangos) {
                if (!is_array($rangos)) {
                    continue;
                }

                $areaId = (int)$areaId;

                foreach ($rangos as $rangoId => $payload) {
                    if (!is_array($payload)) {
                        continue;
                    }

                    $rangoId = (int)$rangoId;

                    // Área 1: Hematología (auto / manual)
                    if ($areaId === 1) {
                        $valorAuto = $this->parseValorNullable($payload['valor_automatizado'] ?? null);
                        $valorManual = $this->parseValorNullable($payload['valor_manual'] ?? null);

                        if ($valorAuto === null && $valorManual === null) {
                            ResultadoLaboratorio::where('solicitude_id', $solicitud->id)
                                ->where('area_rango_id', $rangoId)
                                ->delete();
                            continue;
                        }

                        $valorFinal = null;
                        $preferido = null;
                        $metodoFinal = null;

                        if ($valorManual !== null) {
                            $valorFinal = $valorManual;
                            $preferido = 'MAN';
                            $metodoFinal = 'MANUAL';
                        } elseif ($valorAuto !== null) {
                            $valorFinal = $valorAuto;
                            $preferido = 'AUTO';
                            $metodoFinal = 'AUTOMATIZADO';
                        }

                        ResultadoLaboratorio::updateOrCreate(
                            [
                                'solicitude_id' => $solicitud->id,
                                'area_rango_id' => $rangoId,
                            ],
                            [
                                'area_id' => $areaId,
                                'valor_automatizado' => $valorAuto,
                                'valor_manual' => $valorManual,
                                'valor_final' => $valorFinal,
                                'preferido' => $preferido,
                                'metodo_final' => $metodoFinal,
                            ]
                        );
                    } else {
                        // Otras áreas: solo valor_final
                        $valor = $this->parseValorNullable($payload['valor'] ?? null);

                        if ($valor === null) {
                            ResultadoLaboratorio::where('solicitude_id', $solicitud->id)
                                ->where('area_rango_id', $rangoId)
                                ->delete();
                            continue;
                        }

                        ResultadoLaboratorio::updateOrCreate(
                            [
                                'solicitude_id' => $solicitud->id,
                                'area_rango_id' => $rangoId,
                            ],
                            [
                                'area_id' => $areaId,
                                'valor_final' => $valor,
                                'metodo_final' => null,
                                'valor_automatizado' => null,
                                'valor_manual' => null,
                                'preferido' => null,
                            ]
                        );
                    }
                }
            }

            // Guardar propiedades extra por área
            $this->guardarPropiedadesArea($solicitud, $propiedadesArea);

            $solicitud->estado = 'FINALIZADO';
            $solicitud->user_analitica_id = auth()->id();
            $solicitud->fecha_envio_analitica = now();
            if (empty($solicitud->fecha_finalizacion)) {
                $solicitud->fecha_finalizacion = now();
            }
            $solicitud->save();
        });

        return response()->json([
            'message' => 'Resultados de analítica guardados correctamente.',
        ]);
    }

    /**
     * Guarda las propiedades extra por área (sangre, suero, sala/cama, etc.)
     *
     * Estructura esperada:
     *  propiedades_area: {
     *    "1": { "aceptada": "ACEPTADA", "coagulo": "NO", "equipo": "Mindray C3510", ... },
     *    "2": { "aceptada": "ACEPTADA", "hemolizada": "NO", ... },
     *    "3": { "sala": "3A", "cama": "12", "paciente_ambulatorio": "SI", ... }
     *  }
     */
    protected function guardarPropiedadesArea(Solicitude $solicitud, array $propiedadesArea): void
    {
        foreach ($propiedadesArea as $areaId => $campos) {
            if (!is_array($campos)) {
                continue;
            }

            $areaId = (int)$areaId;

            foreach ($campos as $campo => $valor) {
                $campo = trim((string)$campo);
                if ($campo === '') {
                    continue;
                }

                if ($valor === null || $valor === '') {
                    SolicitudePropiedad::where('solicitude_id', $solicitud->id)
                        ->where('area_id', $areaId)
                        ->where('campo', $campo)
                        ->delete();
                    continue;
                }

                SolicitudePropiedad::updateOrCreate(
                    [
                        'solicitude_id' => $solicitud->id,
                        'area_id' => $areaId,
                        'campo' => $campo,
                    ],
                    [
                        'valor' => (string)$valor,
                    ]
                );
            }
        }
    }
}
