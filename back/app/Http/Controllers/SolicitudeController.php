<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Solicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');
        $query = Solicitude::with([
            'paciente:id,nombre_completo,ci,telefono',
            'doctor:id,nombre',
            'user:id,name',
            'laboratorioItems:id,solicitude_id,producto_nombre,precio',
        ])->latest('fecha_solicitud')->latest('id');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($subquery) use ($q) {
                $subquery->where('codigo_solicitud', 'like', "%{$q}%")
                    ->orWhereHas('paciente', fn ($patientQuery) => $patientQuery
                        ->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('ci', 'like', "%{$q}%"));
            });
        }
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }
        if ($desde = $request->input('desde')) {
            $query->whereDate('fecha_solicitud', '>=', $desde);
        }
        if ($hasta = $request->input('hasta')) {
            $query->whereDate('fecha_solicitud', '<=', $hasta);
        }

        return response()->json($query->paginate((int) $request->input('per_page', 15)));
    }

    public function formData(Request $request)
    {
        $this->authorizeAny($request, ['Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);

        return response()->json([
            'doctores' => Doctor::where('estado', 'ACTIVO')->orderBy('nombre')->get(['id', 'nombre', 'registro']),
            'laboratorios' => Producto::query()
                ->whereHas('tipoProducto', fn ($query) => $query->where('nombre', 'LABORATORIOS'))
                ->with('laboratorioDatos.formula')
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'precio']),
        ]);
    }

    public function pacientes(Request $request)
    {
        $this->authorizeAny($request, ['Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);
        $query = Paciente::query()->orderBy('nombre_completo');
        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($subquery) use ($search) {
                $subquery->where('nombre_completo', 'like', "%{$search}%")
                    ->orWhere('ci', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->paginate(min((int) $request->input('per_page', 20), 50), ['id', 'nombre_completo', 'ci', 'sexo'])
        );
    }

    public function store(Request $request)
    {
        $this->authorizeAny($request, 'Crear Solicitudes Laboratorio');
        [$validated, $productos, $valores] = $this->validatedPayload($request);

        $solicitud = DB::transaction(function () use ($validated, $productos, $request, $valores) {
            $solicitud = Solicitude::create([
                ...collect($validated)->except(['producto_ids', 'resultados'])->all(),
                'user_id' => $request->user()->id,
                'estado' => 'CREADO',
                'total' => $productos->sum('precio'),
            ]);
            $solicitud->update([
                'codigo_solicitud' => 'LAB-'.$solicitud->fecha_solicitud->format('Ymd').'-'.str_pad((string) $solicitud->id, 5, '0', STR_PAD_LEFT),
            ]);
            $this->syncLaboratorios($solicitud, $productos, $valores);

            return $solicitud;
        });

        return response()->json($solicitud->load(['paciente', 'doctor', 'laboratorioItems.resultados']), 201);
    }

    public function show(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');

        return response()->json($solicitude->load(['paciente', 'doctor', 'user', 'laboratorioItems.resultados']));
    }

    public function update(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Editar Solicitudes Laboratorio');
        abort_unless($solicitude->estado === 'CREADO', 422, 'Solo se pueden modificar solicitudes en estado CREADO.');
        [$validated, $productos, $valores] = $this->validatedPayload($request);

        DB::transaction(function () use ($solicitude, $validated, $productos, $valores) {
            $solicitude->update([
                ...collect($validated)->except(['producto_ids', 'resultados'])->all(),
                'total' => $productos->sum('precio'),
            ]);
            $this->syncLaboratorios($solicitude, $productos, $valores);
        });

        return response()->json($solicitude->load(['paciente', 'doctor', 'laboratorioItems.resultados']));
    }

    public function pdf(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');
        $solicitude->load(['paciente', 'doctor.especialidades:id,nombre', 'user', 'laboratorioItems.resultados']);

        return Pdf::loadView('reportes.solicitud-laboratorio', compact('solicitude'))
            ->setPaper('letter')
            ->stream('laboratorio_'.$solicitude->codigo_solicitud.'.pdf');
    }

    public function destroy(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Eliminar Solicitudes Laboratorio');
        abort_unless($solicitude->estado === 'CREADO', 422, 'Solo se pueden eliminar solicitudes en estado CREADO.');
        $solicitude->delete();

        return response()->json(['message' => 'Solicitud eliminada']);
    }

    private function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'paciente_id' => ['required', 'exists:pacientes,id'],
            'doctor_id' => ['nullable', 'exists:doctores,id'],
            'fecha_solicitud' => ['required', 'date'],
            'hora_solicitud' => ['required', 'date_format:H:i'],
            'diagnostico_clinico' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'producto_ids' => ['required', 'array', 'min:1'],
            'producto_ids.*' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'resultados' => ['nullable', 'array'],
            'resultados.*.producto_laboratorio_dato_id' => ['required', 'integer'],
            'resultados.*.valor' => ['nullable', 'string', 'max:2000'],
        ]);
        $productos = Producto::query()
            ->whereIn('id', $validated['producto_ids'])
            ->whereHas('tipoProducto', fn ($query) => $query->where('nombre', 'LABORATORIOS'))
            ->with('laboratorioDatos.formula')
            ->get();
        abort_unless($productos->count() === count($validated['producto_ids']), 422, 'Seleccione únicamente productos de laboratorio.');

        return [
            $validated,
            $productos,
            collect($validated['resultados'] ?? [])->keyBy('producto_laboratorio_dato_id'),
        ];
    }

    private function syncLaboratorios(Solicitude $solicitud, $productos, $valores): void
    {
        foreach ($solicitud->laboratorioItems()->with('resultados')->get() as $itemAnterior) {
            $itemAnterior->resultados->each->delete();
            $itemAnterior->delete();
        }
        foreach ($productos as $producto) {
            $item = $solicitud->laboratorioItems()->create([
                'producto_id' => $producto->id,
                'producto_nombre' => $producto->nombre,
                'precio' => $producto->precio,
            ]);
            foreach ($producto->laboratorioDatos as $dato) {
                $item->resultados()->create([
                    'producto_laboratorio_dato_id' => $dato->id,
                    'nombre' => $dato->nombre,
                    'nombre_variable' => $dato->nombre_variable,
                    'unidad' => $dato->unidad,
                    'rango_referencia' => $dato->rango_referencia,
                    'formula' => $dato->formula?->formula,
                    'valor' => $valores->get($dato->id)['valor'] ?? null,
                    'orden' => $dato->orden,
                    'visible' => $dato->visible,
                ]);
            }
        }
    }

    private function authorizeAny(Request $request, string|array $permissions): void
    {
        foreach ((array) $permissions as $permission) {
            if ($request->user()->hasPermissionTo($permission)) {
                return;
            }
        }
        abort(403, 'No tiene permiso para realizar esta acción');
    }
}
