<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Recogido;
use App\Models\Solicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecogidoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $from = $request->get('from');
        $to = $request->get('to');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;

        $areaId = $request->filled('area_id') ? (int) $request->get('area_id') : null;
        $estado = $request->get('estado', ''); // pendiente | con_resultado | recogido

        $query = Solicitude::query()
            ->with([
                'doctor',
                'servicioSolicitudes' => function ($q) use ($areaId) {
                    $q->with(['servicio.area', 'area'])
                        ->when($areaId, fn($x) => $x->where('area_id', $areaId))
                        ->orderBy('id', 'asc');
                },
            ])
            ->whereHas('servicioSolicitudes', function ($q) use ($areaId) {
                $q->when($areaId, fn($x) => $x->where('area_id', $areaId));
            })
            ->when($from, fn($q) => $q->whereDate('fecha_solicitud', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('fecha_solicitud', '<=', $to))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('paciente_nombre', 'like', "%{$search}%")
                        ->orWhere('paciente_ci', 'like', "%{$search}%")
                        ->orWhere('doctor_nombre', 'like', "%{$search}%")
                        ->orWhere('nro_registro', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%");
                });
            })
            ->when($estado === 'pendiente', function ($q) use ($areaId) {
                // Ningún servicio del área tiene resultado
                $q->whereDoesntHave('servicioSolicitudes', function ($w) use ($areaId) {
                    $w->where('realizado', 'REALIZADO')
                      ->when($areaId, fn($x) => $x->where('area_id', $areaId));
                });
            })
            ->when($estado === 'con_resultado', function ($q) use ($areaId) {
                // Al menos uno realizado pero ninguno recogido en el área
                $q->whereHas('servicioSolicitudes', function ($w) use ($areaId) {
                    $w->where('realizado', 'REALIZADO')
                      ->when($areaId, fn($x) => $x->where('area_id', $areaId));
                })->whereDoesntHave('servicioSolicitudes', function ($w) use ($areaId) {
                    $w->where('fue_recogido', true)
                      ->when($areaId, fn($x) => $x->where('area_id', $areaId));
                });
            })
            ->when($estado === 'recogido', function ($q) use ($areaId) {
                // Al menos uno recogido en el área
                $q->whereHas('servicioSolicitudes', function ($w) use ($areaId) {
                    $w->where('fue_recogido', true)
                      ->when($areaId, fn($x) => $x->where('area_id', $areaId));
                });
            })
            ->orderByDesc('id');

        $rows = $query->paginate($perPage);

        // Presentacion info desde tablas de resultado de laboratorio
        $solicitudeIds = collect($rows->items())->pluck('id')->toArray();
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
                        $r->fecha_presentacion > ($presentaciones[$sid]['raw'] ?? null)
                    ) {
                        $presentaciones[$sid] = [
                            'raw'                => $r->fecha_presentacion,
                            'user_presentacion'  => $r->userPresentacion?->name,
                            'fecha_presentacion' => $r->fecha_presentacion?->format('d/m/Y H:i'),
                        ];
                    }
                });
        }

        $items = collect($rows->items())->map(function ($row) use ($presentaciones) {
            $p = $presentaciones[$row->id] ?? null;
            $row->user_presentacion  = $p['user_presentacion']  ?? null;
            $row->fecha_presentacion = $p['fecha_presentacion'] ?? null;
            return $row;
        });

        return response()->json([
            'rows' => $items->values(),
            'pagination' => [
                'page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'rows_number' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
            'area' => $areaId ? Area::find($areaId) : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $row = Recogido::with(['servicio.area', 'solicitud'])->findOrFail($id);

        $user = $request->user();
        if (($user->role ?? null) !== 'Administrador' && (int) $row->area_id !== (int) $user->area_id) {
            return response()->json(['message' => 'No autorizado para actualizar este servicio'], 403);
        }

        $data = $request->validate([
            'fue_recogido' => 'required|boolean',
            'recogido_por_personal' => 'nullable|string|max:255',
            'grado_parentesco' => 'nullable|string|max:120',
            'telefono_recogido' => 'nullable|string|max:40',
            'ci_recogido' => 'nullable|string|max:40',
        ]);

        if ($data['fue_recogido']) {
            $request->validate([
                'recogido_por_personal' => 'required|string|max:255',
                'grado_parentesco' => 'required|string|max:120',
                'telefono_recogido' => 'required|string|max:40',
                'ci_recogido' => 'required|string|max:40',
            ]);
            $data['recogido_en_dia'] = now();
        } else {
            $data['recogido_por_personal'] = null;
            $data['grado_parentesco'] = null;
            $data['telefono_recogido'] = null;
            $data['ci_recogido'] = null;
            $data['recogido_en_dia'] = null;
        }

        $row->update($data);

        return response()->json($row->fresh(['servicio.area', 'area', 'solicitud']));
    }

    public function recogerArea(Request $request)
    {
        $data = $request->validate([
            'solicitude_id' => 'required|integer|exists:solicitudes,id',
            'area_id' => 'required|integer|exists:areas,id',
            'fue_recogido' => 'required|boolean',
            'recogido_por_personal' => 'nullable|string|max:255',
            'grado_parentesco' => 'nullable|string|max:120',
            'telefono_recogido' => 'nullable|string',
            'ci_recogido' => 'nullable|string|max:40',
        ]);

        if ($data['fue_recogido']) {
            $request->validate([
                'recogido_por_personal' => 'required|string|max:255',
                'grado_parentesco' => 'nullable|string|max:120',
                'telefono_recogido' => 'nullable|string|max:40',
                'ci_recogido' => 'nullable|string|max:40',
            ]);
            $data['recogido_en_dia'] = now();
        } else {
            $data['recogido_por_personal'] = null;
            $data['grado_parentesco'] = null;
            $data['telefono_recogido'] = null;
            $data['ci_recogido'] = null;
            $data['recogido_en_dia'] = null;
        }

        $user = $request->user();
//        if (($user->role ?? null) !== 'Administrador' && (int) $data['area_id'] !== (int) $user->area_id) {
//            return response()->json(['message' => 'No autorizado para actualizar esta area'], 403);
//        }

        $query = Recogido::query()
            ->where('solicitude_id', $data['solicitude_id'])
            ->where('area_id', $data['area_id']);

        $count = (clone $query)->count();
        if ($count === 0) {
            return response()->json(['message' => 'No existen servicios de esta area para la solicitud'], 404);
        }

        DB::transaction(function () use ($query, $data) {
            $query->update([
                'fue_recogido' => $data['fue_recogido'],
                'recogido_por_personal' => $data['recogido_por_personal'],
                'grado_parentesco' => $data['grado_parentesco'],
                'telefono_recogido' => $data['telefono_recogido'],
                'ci_recogido' => $data['ci_recogido'],
                'recogido_en_dia' => $data['recogido_en_dia'],
            ]);
        });

        $rows = Recogido::with(['servicio.area', 'area'])
            ->where('solicitude_id', $data['solicitude_id'])
            ->where('area_id', $data['area_id'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'message' => 'Recogido actualizado por area',
            'solicitude_id' => (int) $data['solicitude_id'],
            'area_id' => (int) $data['area_id'],
            'updated_count' => $count,
            'rows' => $rows,
        ]);
    }

    public function reportePdf(Request $request)
    {
        $tipo = (string) $request->get('tipo', 'dia'); // dia|area|pendientes|activos|recogidos
        $search = trim((string) $request->get('search', ''));
        $from = $request->get('from');
        $to = $request->get('to');
        $date = $request->get('date', now()->toDateString());

        $areaId = $request->filled('area_id') ? (int) $request->get('area_id') : null;

        $query = DB::table('servicio_solicitudes as ss')
            ->join('solicitudes as s', 's.id', '=', 'ss.solicitude_id')
            ->leftJoin('areas as a', 'a.id', '=', 'ss.area_id')
            ->leftJoin('servicios as se', 'se.id', '=', 'ss.servicio_id')
            ->whereNull('ss.deleted_at')
            ->whereNull('s.deleted_at')
            ->when($areaId, fn($q) => $q->where('ss.area_id', $areaId))
            ->when($from, fn($q) => $q->whereDate('s.fecha_solicitud', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('s.fecha_solicitud', '<=', $to))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('s.paciente_nombre', 'like', "%{$search}%")
                        ->orWhere('s.doctor_nombre', 'like', "%{$search}%")
                        ->orWhere('s.nro_registro', 'like', "%{$search}%")
                        ->orWhere('s.codigo', 'like', "%{$search}%")
                        ->orWhere('ss.recogido_por_personal', 'like', "%{$search}%")
                        ->orWhere('ss.ci_recogido', 'like', "%{$search}%");
                });
            });

        switch ($tipo) {
            case 'pendientes':
                $query->where(function ($q) {
                    $q->whereNull('ss.fue_recogido')->orWhere('ss.fue_recogido', 0);
                });
                break;
            case 'activos':
                $query->where('ss.realizado', 'REALIZADO');
                break;
            case 'recogidos':
                $query->where('ss.fue_recogido', 1);
                break;
            case 'area':
                // Solo aplica filtros generales + area_id.
                break;
            case 'dia':
            default:
                $query->whereDate('s.fecha_solicitud', '=', $date);
                break;
        }

        $rows = $query
            ->select(
                'ss.id',
                'ss.solicitude_id',
                'ss.area_id',
                'ss.realizado',
                'ss.fue_recogido',
                'ss.recogido_por_personal',
                'ss.ci_recogido',
                'ss.telefono_recogido',
                'ss.grado_parentesco',
                'ss.recogido_en_dia',
                'ss.nombre as servicio_nombre_pivot',
                'se.nombre as servicio_nombre_catalogo',
                'a.name as area_name',
                'a.title as area_title',
                's.codigo',
                's.nro_registro',
                's.paciente_nombre',
                's.paciente_ci',
                's.paciente_telefono',
                's.doctor_nombre',
                's.fecha_solicitud',
                's.fecha_creacion'
            )
            ->orderByDesc('ss.recogido_en_dia')
            ->orderByDesc('ss.id')
            ->get();

        $total = $rows->count();
        $totalRecogidos = $rows->where('fue_recogido', 1)->count();
        $totalPendientes = $rows->where('fue_recogido', 0)->count();
        $totalRealizados = $rows->where('realizado', 'REALIZADO')->count();

        $labels = [
            'dia' => 'Reporte por dia',
            'area' => 'Reporte por area',
            'pendientes' => 'Reporte pendientes',
            'activos' => 'Reporte activos',
            'recogidos' => 'Reporte recogidos',
        ];

        $pdf = Pdf::loadView('reportes.recogidos_resumen', [
            'rows' => $rows,
            'tipo' => $tipo,
            'titulo' => $labels[$tipo] ?? 'Reporte recogidos',
            'date' => $date,
            'from' => $from,
            'to' => $to,
            'search' => $search,
            'area' => $areaId ? Area::find($areaId) : null,
            'generado' => now(),
            'totales' => [
                'total' => $total,
                'recogidos' => $totalRecogidos,
                'pendientes' => $totalPendientes,
                'realizados' => $totalRealizados,
            ],
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_recogidos_'.$tipo.'_'.now()->format('Ymd_His').'.pdf');
    }
}
