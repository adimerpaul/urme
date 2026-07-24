<?php

namespace App\Http\Controllers;

use App\Models\EntregaResultado;
use App\Models\Solicitude;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EntregaResultadoController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->get('to', Carbon::now()->endOfMonth()->toDateString());
        $search = trim((string) $request->get('search', ''));
        $estado = $request->get('estado', '');
        $perPage = min((int) $request->get('per_page', 9999), 9999);
        $page = max((int) $request->get('page', 1), 1);

        $query = Solicitude::with([
            'servicios' => fn ($q) => $q->with('area'),
            'entregaResultados.user',
        ])
            ->whereDate('fecha_solicitud', '>=', $from)
            ->whereDate('fecha_solicitud', '<=', $to)
            ->whereHas('servicioSolicitudes', fn ($q) => $q->where('realizado', 'REALIZADO'))
            ->when($search !== '', function ($q) use ($search) {
                $isNumeric = ctype_digit($search);
                $q->where(function ($w) use ($search, $isNumeric) {
                    if ($isNumeric) {
                        $w->where('codigo', 'like', "%{$search}%");
                    } else {
                        $w->where('paciente_nombre', 'like', "%{$search}%")
                            ->orWhere('paciente_ci', 'like', "%{$search}%");
                    }
                });
            })
            ->when($estado === 'entregado', fn ($q) => $q->whereHas('entregaResultados'))
        // pendiente = tiene al menos un área cuyos resultados aún no fueron entregados
        // (la entrega se registra por área, no por solicitud completa)
            ->when($estado === 'pendiente', fn ($q) => $q->whereRaw('EXISTS (
            SELECT 1 FROM servicio_solicitudes ss
            JOIN servicios sv ON sv.id = ss.servicio_id
            JOIN areas a ON a.id = sv.area_id
            WHERE ss.solicitude_id = solicitudes.id
              AND NOT EXISTS (
                SELECT 1 FROM entrega_resultados er
                WHERE er.solicitude_id = solicitudes.id
                  AND er.area = a.name
              )
        )'))
            ->orderByDesc('fecha_solicitud')
            ->orderByDesc('id');

        $total = $query->count();
        $rows = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'rows' => $rows,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function registrar(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.solicitude_id' => 'required|integer|exists:solicitudes,id',
            'items.*.area' => 'required|string|max:100',
        ]);

        $user = auth()->user();
        $now = Carbon::now();
        $fecha = $now->toDateString();
        $hora = $now->format('H:i:s');

        foreach ($request->items as $item) {
            EntregaResultado::updateOrCreate(
                [
                    'solicitude_id' => $item['solicitude_id'],
                    'area' => $item['area'],
                ],
                [
                    'user_id' => $user->id,
                    'fecha_entrega' => $fecha,
                    'hora_entrega' => $hora,
                ]
            );
        }

        return response()->json([
            'message' => 'Entrega registrada correctamente',
            'count' => count($request->items),
        ]);
    }
}
