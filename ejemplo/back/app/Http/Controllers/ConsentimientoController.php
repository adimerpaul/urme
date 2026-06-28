<?php
// app/Http/Controllers/ConsentimientoController.php

namespace App\Http\Controllers;

use App\Models\Consentimiento;
use App\Models\Paciente;
use App\Models\Solicitude;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ConsentimientoController extends Controller
{
    public function showBySolicitude($solicitudeId)
    {
        $solicitud = Solicitude::findOrFail($solicitudeId);

        $consentimiento = Consentimiento::where('solicitude_id', $solicitudeId)
            ->with('paciente')
            ->first();

        if ($consentimiento) {
            return response()->json($consentimiento);
        }

        return response()->json([
            'solicitude_id' => $solicitud->id,
            'paciente_id' => $solicitud->paciente_id,
            'fecha_solicitud' => $solicitud->fecha_solicitud,
            'nombre_completo' => $solicitud->paciente_nombre,
            'fecha_nac' => $solicitud->paciente_fecha_nac,
            'genero' => $solicitud->paciente_genero,
            'edad' => $solicitud->paciente_edad,
            'ci' => $solicitud->paciente_ci,
            'telefono' => $solicitud->paciente_telefono,
            'direccion' => $solicitud->paciente_direccion,
            'discapacidad' => $solicitud->paciente_discapacidad,
            'discapacidad_cual' => $solicitud->paciente_discapacidad_cual,
            'discapacidad_otro' => $solicitud->paciente_discapacidad_otro,
            'embarazo' => $solicitud->paciente_embarazo,
            'fum' => $solicitud->paciente_fum,
            'sem_gest' => $solicitud->paciente_sem_gest,
            'medicamento' => 0,
            'tratamiento' => null,
            'condicion' => null,
            'etapa_gestacion' => null,
            'tipo' => null,
            'declarante_nombre' => null,
            'declarante_condicion' => null,
            'declarante_condicion_otro' => null,
            'fecha_consentimiento' => now()->toDateString(),
            'fecha_recepcion' => now()->toDateString(),
            'hora_recepcion' => now()->format('H:i'),
            'm_orina' => 0,
            'hr_recoleccion_orina' => null,
            'm_liquidos' => 0,
            'm_esputo' => 0,
            'm_secreciones' => 0,
            'm_heces' => 0,
            'hr_recoleccion_heces' => null,
            'observaciones' => null,
        ]);
    }

    public function upsertBySolicitude(Request $request, $solicitudeId)
    {
        $solicitud = Solicitude::findOrFail($solicitudeId);

        $request->validate([
            'tipo' => 'nullable|in:ACEPTA,RECHAZA',
            'fecha_consentimiento' => 'nullable|date',
            'fecha_recepcion' => 'nullable|date',
            'hora_recepcion' => 'nullable',
        ]);

        $payload = $request->all();
        $payload['solicitude_id'] = $solicitud->id;
        $payload['paciente_id'] = $payload['paciente_id'] ?? $solicitud->paciente_id;

        if ($request->user()) {
            $payload['user_id'] = $request->user()->id;
        }

        $consentimiento = Consentimiento::where('solicitude_id', $solicitud->id)->first();

        if ($consentimiento) {
            $consentimiento->update($payload);
        } else {
            $consentimiento = Consentimiento::create($payload);
        }

        return response()->json($consentimiento->load('paciente'));
    }
    public function reporte(Request $request)
    {
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $userId   = $request->input('user_id');
        $groupBy  = $request->input('group_by', 'day'); // day | week | month

        // ===== BASE QUERY (con filtros) =====
        $base = Consentimiento::query()
            ->when($dateFrom, fn($q) => $q->whereDate('fecha_consentimiento', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('fecha_consentimiento', '<=', $dateTo))
            ->when($userId,   fn($q) => $q->where('user_id', $userId));

        // ===== RESUMEN GENERAL =====
        $total = (clone $base)->count();

        $acepta = (clone $base)->where('tipo', 'ACEPTA')->count();
        $rechaza = (clone $base)->where('tipo', 'RECHAZA')->count();

        $resumen = [
            'total' => $total,
            'acepta' => $acepta,
            'rechaza' => $rechaza,
            'pct_acepta' => $total ? round(($acepta * 100) / $total, 2) : 0,
            'pct_rechaza' => $total ? round(($rechaza * 100) / $total, 2) : 0,
        ];

        // ===== TABLA: POR USUARIO =====
        $porUsuario = (clone $base)
            ->leftJoin('users', 'users.id', '=', 'consentimientos.user_id')
            ->select([
                'consentimientos.user_id',
                DB::raw("COALESCE(users.name, 'SIN USUARIO') as user_name"),
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN consentimientos.tipo='ACEPTA' THEN 1 ELSE 0 END) as acepta"),
                DB::raw("SUM(CASE WHEN consentimientos.tipo='RECHAZA' THEN 1 ELSE 0 END) as rechaza"),
            ])
            ->groupBy('consentimientos.user_id', 'users.name')
            ->orderByDesc('total')
            ->get()
            ->map(function ($r) {
                $r->pct_acepta = $r->total ? round(($r->acepta * 100) / $r->total, 2) : 0;
                $r->pct_rechaza = $r->total ? round(($r->rechaza * 100) / $r->total, 2) : 0;
                return $r;
            });

        // ===== SERIE TIEMPO (histograma): day/week/month =====
        // Nota: esto asume MySQL/MariaDB.
        if ($groupBy === 'month') {
            $periodExpr = DB::raw("DATE_FORMAT(fecha_consentimiento, '%Y-%m') as periodo");
            $orderExpr  = DB::raw("DATE_FORMAT(fecha_consentimiento, '%Y-%m')");

        } elseif ($groupBy === 'week') {
            // YEARWEEK con modo 3 = semana ISO (lunes como inicio) en MySQL
            $periodExpr = DB::raw("CONCAT(YEAR(fecha_consentimiento), '-W', LPAD(WEEK(fecha_consentimiento, 3), 2, '0')) as periodo");
            $orderExpr  = DB::raw("YEAR(fecha_consentimiento), WEEK(fecha_consentimiento, 3)");

        } else {
            // day
            $periodExpr = DB::raw("DATE(fecha_consentimiento) as periodo");
            $orderExpr  = DB::raw("DATE(fecha_consentimiento)");
        }

        $serieTiempo = (clone $base)
            ->select([
                $periodExpr,
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN tipo='ACEPTA' THEN 1 ELSE 0 END) as acepta"),
                DB::raw("SUM(CASE WHEN tipo='RECHAZA' THEN 1 ELSE 0 END) as rechaza"),
            ])
            ->groupBy('periodo')
            ->orderBy($orderExpr)
            ->get();

        // ===== TOP (últimos) para tabla pequeña (opcional) =====
        $ultimos = (clone $base)
            ->with(['user:id,name', 'paciente:id,nombre_completo,ci'])
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'fecha_consentimiento' => $c->fecha_consentimiento,
                    'tipo' => $c->tipo,
                    'paciente' => $c->paciente ? $c->paciente->nombre_completo : $c->nombre_completo,
                    'ci' => $c->paciente ? $c->paciente->ci : $c->ci,
                    'user' => $c->user ? $c->user->name : 'SIN USUARIO',
                ];
            });

        return response()->json([
            'resumen' => $resumen,
            'por_usuario' => $porUsuario,
            'serie_tiempo' => $serieTiempo,
            'ultimos' => $ultimos,
        ]);
    }
    public function index(Request $request)
    {
        $query = Consentimiento::with('paciente');

        // filtros opcionales por fecha y tipo
        if ($request->filled('from')) {
            $query->whereDate('fecha_consentimiento', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('fecha_consentimiento', '<=', $request->to);
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);  // ACEPTA / RECHAZA
        }

        return $query->orderBy('id', 'desc')->get();
    }

    public function show($id)
    {
        return Consentimiento::with('paciente')->findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo'               => 'nullable|in:ACEPTA,RECHAZA',
            'paciente_id'        => 'nullable|exists:pacientes,id',
            'nombre_completo'    => 'required_without:paciente_id|string|max:255',
            'ci'                 => 'nullable|string|max:50',
            'fecha_consentimiento' => 'nullable|date',
        ]);

        // Si viene paciente_id y no se enviaron los campos, los completamos con los datos del paciente
//        if ($request->filled('paciente_id')) {
//            $paciente = Paciente::find($request->paciente_id);
//            if ($paciente && !$request->filled('nombre_completo')) {
//                $request->merge([
//                    'nombre_completo' => $paciente->nombre_completo,
//                    'fecha_nac'       => $paciente->fecha_nac,
//                    'genero'          => $paciente->genero,
//                    'edad'            => $paciente->edad,
//                    'ci'              => $paciente->ci,
//                    'telefono'        => $paciente->telefono,
//                    'direccion'       => $paciente->direccion,
//                    'discapacidad'    => $paciente->discapacidad,
//                    'discapacidad_cual' => $paciente->discapacidad_cual,
//                    'embarazo'        => $paciente->embarazo,
//                    'fum'             => $paciente->fum,
//                    'sem_gest'        => $paciente->sem_gest,
//                ]);
//            }
//        }

        // usuario que registra
        if ($request->user()) {
            $request->merge(['user_id' => $request->user()->id]);
        }
        $paciente = $this->upsert($request);
        error_log("Paciente ID: " . $paciente->id);

        $consentimiento = Consentimiento::create($request->all());

        return response()->json($consentimiento->load('paciente'), 201);
    }
    function upsert(Request $request)
    {
        if ($request->filled('paciente_id')) {
            return Paciente::find($request->paciente_id);
        } else {
            // Crear nuevo paciente
            $pacienteData = $request->only([
                'nombre_completo', 'ci', 'fecha_nac', 'genero', 'edad',
                'telefono', 'direccion', 'discapacidad', 'discapacidad_cual',
                'discapacidad_otro', 'embarazo', 'fum', 'sem_gest'
            ]);
            $paciente = Paciente::create($pacienteData);
            // Actualizar el request con el nuevo paciente_id
            $request->merge(['paciente_id' => $paciente->id]);
            return $paciente;
        }
    }

    public function update(Request $request, $id)
    {
        $consentimiento = Consentimiento::findOrFail($id);

        $request->validate([
            'tipo' => 'nullable',
            'paciente_id' => 'nullable|exists:pacientes,id',
        ]);

        $consentimiento->update($request->all());

        return response()->json($consentimiento->load('paciente'));
    }

    public function destroy($id)
    {
        $consentimiento = Consentimiento::findOrFail($id);
        $consentimiento->delete();

        return response()->json(['message' => 'Consentimiento eliminado correctamente']);
    }

    /**
     * Imprimir con DomPDF
     */
    public function print($id)
    {
        $consentimiento = Consentimiento::with('paciente')->findOrFail($id);

        $pdf = Pdf::loadView('pdf.consentimiento', [
            'c' => $consentimiento,
        ])->setPaper('legal', 'portrait');

        return $pdf->stream('consentimiento-'.$consentimiento->id.'.pdf');
    }

    public function printBySolicitude($solicitudeId)
    {
        $consentimiento = Consentimiento::with('paciente')
            ->where('solicitude_id', $solicitudeId)
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.consentimiento', [
            'c' => $consentimiento,
        ])->setPaper('legal', 'portrait');

        return $pdf->stream('consentimiento-solicitud-'.$solicitudeId.'.pdf');
    }
}
