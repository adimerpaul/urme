<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\Producto;
use App\Models\Solicitude;
use App\Models\SolicitudLaboratorioItem;
use App\Models\SolicitudLaboratorioPerfil;
use App\Models\SolicitudLaboratorioResultado;
use App\Models\User;
use App\Models\Venta;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class SolicitudeController extends Controller
{
    /** Hasta cuántas filas un perfil va entero en una página (ver docs/ANALISIS_PERFILES_FASE0.md, F4.4). */
    private const FILAS_PERFIL_SIN_PARTIR = 12;

    /** Hasta cuántas filas un examen (o un grupo_reporte) se imprime sin partirse entre páginas. */
    private const FILAS_PRUEBA_SIN_PARTIR = 12;

    public function index(Request $request)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');
        $query = Solicitude::with([
            'paciente:id,nombre_completo,ci,telefono',
            'doctor:id,nombre',
            'user:id,name',
            'laboratorioItems:id,solicitude_id,solicitud_laboratorio_perfil_id,producto_nombre,precio',
            'laboratorioPerfiles:id,solicitude_id,nombre,precio,orden',
        ])->latest('fecha_solicitud')->latest('id');

        if ($q = trim((string) $request->input('q'))) {
            $query->where(function ($subquery) use ($q) {
                $subquery->where('codigo_solicitud', 'like', "%{$q}%")
                    ->orWhere('codigo_laboratorio', 'like', "%{$q}%")
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

        $perfiles = config('laboratorio.perfiles_habilitado');
        $laboratorios = Producto::query()
            ->whereHas('tipoProducto', fn ($query) => $query->where('es_laboratorio', true))
            ->when(! $perfiles, fn ($query) => $query->where('es_perfil', false))
            ->with(['laboratorioDatos.formula', 'laboratorioDatos.opciones', 'laboratorioValidaciones' => fn ($query) => $query->where('activo', true)])
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'precio', 'descripcion', 'es_perfil', 'tipo_producto_id']);

        // Cada perfil lleva sus exámenes (en orden) y, si no se puede usar, el motivo.
        // Se cargan de una vez para todos los perfiles: no hay una consulta por perfil.
        $perfilesCargados = $laboratorios->where('es_perfil', true)->values();
        $perfilesCargados->load([
            'tipoProducto:id,es_laboratorio',
            'perfilItems.producto' => fn ($query) => $query->withTrashed()->with('tipoProducto:id,es_laboratorio'),
        ]);
        $perfilesCargados->each(function (Producto $perfil) {
            $perfil->setAttribute('examen_ids', $perfil->perfilItems->pluck('producto_id'));
            $perfil->setAttribute('motivo_no_vendible', $perfil->motivoPerfilNoVendible());
            $perfil->unsetRelation('perfilItems')->unsetRelation('tipoProducto');
        });

        return response()->json([
            'doctores' => Doctor::where('estado', 'ACTIVO')->orderBy('nombre')->get(['id', 'nombre', 'registro']),
            'laboratorios' => $laboratorios->values(),
            'perfiles_habilitado' => (bool) $perfiles,
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
            $query->paginate(min((int) $request->input('per_page', 20), 50), ['id', 'nombre_completo', 'ci', 'sexo', 'fecha_nacimiento'])
        );
    }

    public function ventasLaboratorio(Request $request)
    {
        $this->authorizeAny($request, ['Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);

        $query = Venta::query()
            ->whereNotNull('paciente_id')
            ->where(function ($pagadas) {
                $pagadas->where('estado', 'ACTIVO')
                    ->orWhere(fn ($pendiente) => $pendiente
                        ->where('estado', 'PENDIENTE')
                        ->whereNotNull('fecha_hora_cobro'));
            })
            ->whereHas('detalles.producto.tipoProducto', fn ($tipo) => $tipo->where('es_laboratorio', true))
            ->with([
                'paciente:id,nombre_completo,ci,sexo,fecha_nacimiento',
                'doctor:id,nombre',
                'user:id,name',
                'cobradoPor:id,name',
                'detalles' => fn ($detalles) => $detalles
                    ->whereHas('producto.tipoProducto', fn ($tipo) => $tipo->where('es_laboratorio', true))
                    ->with('producto:id,codigo,nombre'),
            ])
            ->orderByDesc(DB::raw('COALESCE(fecha_hora_cobro, fecha_hora)'))
            ->orderByDesc('id');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($filtro) use ($search) {
                $filtro->where('id', $search)
                    ->orWhereHas('paciente', fn ($paciente) => $paciente
                        ->where('nombre_completo', 'like', "%{$search}%")
                        ->orWhere('ci', 'like', "%{$search}%"))
                    ->orWhereHas('detalles.producto', fn ($producto) => $producto
                        ->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%"));
            });
        }
        if ($fecha = $request->input('fecha')) {
            $request->validate(['fecha' => ['date']]);
            $query->whereDate(DB::raw('COALESCE(fecha_hora_cobro, fecha_hora)'), $fecha);
        }

        return response()->json($query->paginate(min((int) $request->input('per_page', 15), 50)));
    }

    public function store(Request $request)
    {
        $this->authorizeAny($request, 'Crear Solicitudes Laboratorio');
        [$validated, $plan, $valores] = $this->validatedPayload($request);

        $solicitud = DB::transaction(function () use ($validated, $plan, $request, $valores) {
            $solicitud = Solicitude::create([
                ...collect($validated)->except(['producto_ids', 'resultados'])->all(),
                'user_id' => $request->user()->id,
                'estado' => 'CREADO',
                'total' => $plan['total'],
            ]);
            $solicitud->update([
                'codigo_solicitud' => 'LAB-'.$solicitud->fecha_solicitud->format('Ymd').'-'.str_pad((string) $solicitud->id, 5, '0', STR_PAD_LEFT),
            ]);
            $this->syncLaboratorios($solicitud, $plan, $valores);

            return $solicitud;
        });

        return response()->json($this->conAvisos($solicitud->load(['paciente', 'doctor', 'laboratorioPerfiles', 'laboratorioItems.resultados']), $plan), 201);
    }

    public function show(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Ver Solicitudes Laboratorio');

        return response()->json($solicitude->load(['paciente.seguro', 'doctor', 'user', 'laboratorioPerfiles', 'laboratorioItems.resultados']));
    }

    public function update(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Editar Solicitudes Laboratorio');
        abort_unless($solicitude->estado === 'CREADO', 422, 'Solo se pueden modificar solicitudes en estado CREADO.');
        // Con los perfiles apagados, la pantalla no los conoce: guardarla perdería sus perfiles.
        abort_if(
            ! config('laboratorio.perfiles_habilitado') && $solicitude->laboratorioPerfiles()->exists(),
            422,
            'Esta solicitud incluye perfiles de exámenes y los perfiles están deshabilitados: no se puede modificar.'
        );
        [$validated, $plan, $valores] = $this->validatedPayload($request, $solicitude);

        DB::transaction(function () use ($solicitude, $validated, $plan, $valores) {
            $solicitude->update([
                ...collect($validated)->except(['producto_ids', 'resultados'])->all(),
                'total' => $plan['total'],
            ]);
            $this->syncLaboratorios($solicitude, $plan, $valores);
        });

        return response()->json($this->conAvisos($solicitude->load(['paciente', 'doctor', 'laboratorioPerfiles', 'laboratorioItems.resultados']), $plan));
    }

    /** Agrega los avisos de la expansión (exámenes compartidos) a la respuesta, si los hay. */
    private function conAvisos(Solicitude $solicitud, array $plan): Solicitude
    {
        return $plan['avisos'] ? $solicitud->setAttribute('avisos', $plan['avisos']) : $solicitud;
    }

    public function pdf(Request $request, Solicitude $solicitude)
    {
        return $this->informe($request, $solicitude);
    }

    public function impresion(Request $request, Solicitude $solicitude)
    {
        return $this->informe($request, $solicitude, true);
    }

    private function informe(Request $request, Solicitude $solicitude, bool $html = false)
    {
        $this->authorizeAny($request, ['Ver Solicitudes Laboratorio', 'Crear Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);
        $solicitude->load([
            'paciente.seguro:id,nombre',
            'doctor.especialidades' => fn ($query) => $query
                ->select('especialidades.id', 'especialidades.nombre')
                ->wherePivotNull('deleted_at'),
            'user',
            'laboratorioItems.producto.tipoProducto:id,nombre',
            'laboratorioItems.resultados',
            'laboratorioItems.perfil',
        ]);

        $impresoPor = $request->user();
        $urlVerificacion = rtrim(config('app.frontend_url'), '/').'/verificacion/'.$solicitude->codigo_verificacion;
        $renderer = new ImageRenderer(new RendererStyle(160, 4), new SvgImageBackEnd);
        $qrSvg = (new Writer($renderer))->writeString($urlVerificacion);
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);

        $paraNavegador = $html;
        $bloques = $this->bloquesInforme($solicitude);
        $edadPaciente = $this->edadALaSolicitud($solicitude);
        $especialidades = $solicitude->doctor?->especialidades->pluck('nombre')->implode(', ') ?: '—';
        $datos = compact(
            'solicitude', 'impresoPor', 'urlVerificacion', 'qrDataUri', 'paraNavegador', 'bloques',
            'edadPaciente', 'especialidades'
        );
        if ($html) {
            return response()->view('reportes.solicitud-laboratorio', $datos);
        }

        $pdf = Pdf::loadView('reportes.solicitud-laboratorio', $datos)
            ->setPaper('letter');
        $pdf->render();
        $canvas = $pdf->getDomPDF()->getCanvas();
        $font = $pdf->getDomPDF()->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(438, 772, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 7, [55 / 255, 65 / 255, 81 / 255]);

        return $pdf->stream('laboratorio_'.$solicitude->codigo_solicitud.'.pdf');
    }

    public function auditoria(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, ['Ver Solicitudes Laboratorio', 'Editar Solicitudes Laboratorio']);

        $auditoriasSolicitud = Audit::query()
            ->where('auditable_type', Solicitude::class)
            ->where('auditable_id', $solicitude->id)
            ->get();

        $auditoriasItems = Audit::query()
            ->where('auditable_type', SolicitudLaboratorioItem::class)
            ->where(function ($query) use ($solicitude) {
                $query->where('new_values->solicitude_id', $solicitude->id)
                    ->orWhere('old_values->solicitude_id', $solicitude->id);
            })
            ->get();

        $itemIds = $auditoriasItems->pluck('auditable_id')->unique()->values();
        $auditoriasResultados = $itemIds->isEmpty()
            ? collect()
            : Audit::query()
                ->where('auditable_type', SolicitudLaboratorioResultado::class)
                ->where(function ($query) use ($itemIds) {
                    $query->whereIn('new_values->solicitud_laboratorio_item_id', $itemIds)
                        ->orWhereIn('old_values->solicitud_laboratorio_item_id', $itemIds);
                })
                ->get();

        $auditoriasPerfiles = Audit::query()
            ->where('auditable_type', SolicitudLaboratorioPerfil::class)
            ->where(function ($query) use ($solicitude) {
                $query->where('new_values->solicitude_id', $solicitude->id)
                    ->orWhere('old_values->solicitude_id', $solicitude->id);
            })
            ->get();

        $auditorias = $auditoriasSolicitud
            ->concat($auditoriasPerfiles)
            ->concat($auditoriasItems)
            ->concat($auditoriasResultados)
            ->sortByDesc(fn ($audit) => sprintf('%s-%010d', $audit->created_at, $audit->id))
            ->values();
        $usuarios = User::withTrashed()->whereIn('id', $auditorias->pluck('user_id')->filter()->unique())
            ->get()->keyBy('id');

        return response()->json($auditorias->map(function ($audit) use ($usuarios) {
            $anteriores = $audit->old_values ?? [];
            $nuevos = $audit->new_values ?? [];
            $valores = array_merge($anteriores, $nuevos);
            $camposIgnorados = ['id', 'solicitude_id', 'solicitud_laboratorio_item_id', 'producto_id', 'producto_laboratorio_dato_id',
                'perfil_producto_id', 'solicitud_laboratorio_perfil_id'];
            $campos = collect(array_unique(array_merge(array_keys($anteriores), array_keys($nuevos))))
                ->reject(fn ($campo) => in_array($campo, $camposIgnorados, true))
                ->map(fn ($campo) => [
                    'campo' => $campo,
                    'anterior' => $anteriores[$campo] ?? null,
                    'nuevo' => $nuevos[$campo] ?? null,
                ])->values();

            $entidad = match ($audit->auditable_type) {
                SolicitudLaboratorioResultado::class => 'Resultado: '.($valores['nombre'] ?? '#'.$audit->auditable_id),
                SolicitudLaboratorioItem::class => 'Prueba: '.($valores['producto_nombre'] ?? '#'.$audit->auditable_id),
                SolicitudLaboratorioPerfil::class => 'Perfil: '.($valores['nombre'] ?? '#'.$audit->auditable_id),
                default => 'Solicitud '.($valores['codigo_solicitud'] ?? '#'.$audit->auditable_id),
            };
            $usuario = $usuarios->get($audit->user_id);

            return [
                'id' => $audit->id,
                'fecha' => $audit->created_at,
                'evento' => $audit->event,
                'entidad' => $entidad,
                'usuario' => $usuario?->name ?? $usuario?->username ?? 'Usuario no registrado',
                'cambios' => $campos,
            ];
        }));
    }

    public function verificacion(string $codigo)
    {
        abort_unless(strlen($codigo) === 32, 404);

        $solicitude = Solicitude::query()
            ->where('codigo_verificacion', $codigo)
            ->with(['paciente', 'doctor', 'laboratorioItems.producto.tipoProducto:id,nombre', 'laboratorioItems.resultados', 'laboratorioItems.perfil'])
            ->firstOrFail();

        return response()->json([
            'codigo_solicitud' => $solicitude->codigo_solicitud,
            'codigo_completo' => $solicitude->codigo_completo,
            'codigo_verificacion' => $solicitude->codigo_verificacion,
            'fecha_solicitud' => $solicitude->fecha_solicitud->format('Y-m-d'),
            'hora_solicitud' => substr($solicitude->hora_solicitud, 0, 5),
            'estado' => $solicitude->estado,
            'diagnostico_clinico' => $solicitude->diagnostico_clinico,
            'observaciones' => $solicitude->observaciones,
            'paciente' => [
                'nombre_completo' => $solicitude->paciente->nombre_completo,
                'ci' => $solicitude->paciente->ci,
                'sexo' => $solicitude->paciente->sexo,
            ],
            'doctor' => $solicitude->doctor ? ['nombre' => $solicitude->doctor->nombre] : null,
            // Mismo orden y agrupación por área que el informe impreso (sin descripciones).
            'bloques' => $this->bloquesVerificacion($solicitude),
            'laboratorios' => $solicitude->laboratorioItems
                ->filter(fn ($item) => $item->resultados->where('visible', true)->isNotEmpty())
                ->map(fn ($item) => [
                    'nombre' => $item->producto_nombre,
                    // Nombre del perfil guardado en la solicitud (null: examen suelto).
                    'perfil' => $item->perfil?->nombre,
                    'resultados' => $item->resultados->where('visible', true)->values()->map(fn ($resultado) => [
                        'nombre' => $resultado->nombre,
                        'valor' => $resultado->valor,
                        'unidad' => $resultado->unidad,
                        'rango_referencia' => $resultado->rango_referencia,
                        'rango_referencia_html' => $resultado->rango_referencia_html,
                        'metodo' => $resultado->metodo,
                        'muestra' => $resultado->muestra,
                    ]),
                ])->values(),
        ]);
    }

    public function destroy(Request $request, Solicitude $solicitude)
    {
        $this->authorizeAny($request, 'Eliminar Solicitudes Laboratorio');
        abort_unless($solicitude->estado === 'CREADO', 422, 'Solo se pueden eliminar solicitudes en estado CREADO.');
        $solicitude->delete();

        return response()->json(['message' => 'Solicitud eliminada']);
    }

    /**
     * @param  Solicitude|null  $editando  Solicitud que se modifica (null al crear). El código
     *                                     laboratorio es opcional solo si esa solicitud aún no lo tiene.
     */
    private function validatedPayload(Request $request, ?Solicitude $editando = null): array
    {
        // Se normaliza antes de validar: sin espacios y en mayúsculas ('' queda como null).
        if (is_string($request->input('codigo_laboratorio'))) {
            $request->merge([
                'codigo_laboratorio' => mb_strtoupper(preg_replace('/\s+/u', '', $request->input('codigo_laboratorio'))) ?: null,
            ]);
        }
        $codigoObligatorio = ! $editando || filled($editando->codigo_laboratorio);
        $validated = $request->validate([
            'codigo_laboratorio' => [$codigoObligatorio ? 'required' : 'nullable', 'string', 'max:20', 'regex:/^[A-Z0-9]+(-[A-Z0-9]+)*$/'],
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
            'resultados.*.visible' => ['nullable', 'boolean'],
        ], [
            'codigo_laboratorio.required' => 'El código laboratorio es obligatorio.',
            'codigo_laboratorio.string' => 'El código laboratorio debe ser un texto.',
            'codigo_laboratorio.max' => 'El código laboratorio admite como máximo 20 caracteres.',
            'codigo_laboratorio.regex' => 'El código laboratorio solo admite letras, números y guiones, sin guion al inicio o al final ni dos guiones seguidos.',
        ]);
        $productos = Producto::query()
            ->whereIn('id', $validated['producto_ids'])
            ->whereHas('tipoProducto', fn ($query) => $query->where('es_laboratorio', true))
            // Con los perfiles apagados, un perfil no cuenta como prueba de laboratorio.
            ->when(! config('laboratorio.perfiles_habilitado'), fn ($query) => $query->where('es_perfil', false))
            ->with(['laboratorioDatos.formula', 'laboratorioDatos.opciones'])
            ->get()
            ->sortBy(fn ($producto) => array_search($producto->id, array_map('intval', $validated['producto_ids']), true))
            ->values();
        abort_unless($productos->count() === count($validated['producto_ids']), 422, 'Seleccione únicamente productos de laboratorio.');

        return [
            $validated,
            $this->planLaboratorios($productos),
            collect($validated['resultados'] ?? [])->keyBy('producto_laboratorio_dato_id'),
        ];
    }

    /**
     * Expande los perfiles en sus exámenes, en el orden elegido. Devuelve las filas
     * (un perfil con sus exámenes, o un examen suelto), el total y los avisos.
     * Un examen suelto que ya incluye un perfil se rechaza; un examen compartido
     * por dos perfiles queda solo en el primero.
     */
    private function planLaboratorios($seleccion): array
    {
        $perfiles = $seleccion->where('es_perfil', true)->values();
        foreach ($perfiles as $perfil) {
            if ($motivo = $perfil->motivoPerfilNoVendible()) {
                abort(422, $motivo);
            }
        }

        $composicion = $perfiles->mapWithKeys(fn ($perfil) => [
            $perfil->id => $perfil->perfilItems()->pluck('producto_id')->map(fn ($id) => (int) $id)->all(),
        ]);
        $examenes = Producto::query()
            ->whereIn('id', $composicion->flatten()->unique()->all())
            ->with(['laboratorioDatos.formula', 'laboratorioDatos.opciones'])
            ->get()
            ->keyBy('id');

        foreach ($seleccion->where('es_perfil', false) as $suelto) {
            $perfil = $perfiles->first(fn ($perfil) => in_array($suelto->id, $composicion[$perfil->id], true));
            if ($perfil) {
                abort(422, "{$suelto->nombre} está incluido en el perfil {$perfil->nombre}: quítelo de los exámenes sueltos.");
            }
        }

        $filas = [];
        $asignados = [];
        $avisos = [];
        foreach ($seleccion as $producto) {
            if (! $producto->es_perfil) {
                $filas[] = ['perfil' => null, 'examenes' => [$producto]];

                continue;
            }
            $fila = ['perfil' => $producto, 'examenes' => []];
            foreach ($composicion[$producto->id] as $id) {
                if (isset($asignados[$id])) {
                    $avisos[] = "{$examenes[$id]->nombre} está en {$producto->nombre} y en {$asignados[$id]}: aparece una sola vez, en {$asignados[$id]}.";

                    continue;
                }
                $asignados[$id] = $producto->nombre;
                $fila['examenes'][] = $examenes[$id];
            }
            $filas[] = $fila;
        }

        // Los exámenes de un perfil van en 0: el perfil se cobra por su precio.
        $total = collect($filas)->sum(fn ($fila) => $fila['perfil']
            ? (float) $fila['perfil']->precio
            : (float) $fila['examenes'][0]->precio);

        return ['filas' => $filas, 'total' => round($total, 2), 'avisos' => $avisos];
    }

    /**
     * Edad del paciente a la fecha de la solicitud (no a la de impresión, para que
     * una reimpresión no la cambie): "49 AÑOS", "8 MESES" o "12 DÍAS". Devuelve "—"
     * si la fecha de nacimiento falta, es inválida o es posterior a la solicitud.
     */
    private function edadALaSolicitud(Solicitude $solicitude): string
    {
        $nacimiento = $solicitude->paciente?->getAttributes()['fecha_nacimiento'] ?? null;
        if (! is_string($nacimiento) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $nacimiento, $partes)
            || ! checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1]) || ! $solicitude->fecha_solicitud) {
            return '—';
        }

        $desde = Carbon::createFromDate((int) $partes[1], (int) $partes[2], (int) $partes[3])->startOfDay();
        $hasta = $solicitude->fecha_solicitud->copy()->startOfDay();
        if ($desde->gt($hasta)) {
            return '—';
        }

        $diferencia = $desde->diff($hasta);

        return match (true) {
            $diferencia->y > 0 => $diferencia->y.($diferencia->y === 1 ? ' AÑO' : ' AÑOS'),
            $diferencia->m > 0 => $diferencia->m.($diferencia->m === 1 ? ' MES' : ' MESES'),
            default => $diferencia->d.($diferencia->d === 1 ? ' DÍA' : ' DÍAS'),
        };
    }

    /**
     * Bloques del informe en orden de impresión:
     * - un bloque por área (tipo_productos) con los exámenes sueltos: cada área
     *   sale una sola vez, en la posición de su primer examen, con todos los
     *   sueltos de esa área juntos en el orden de la solicitud. Dentro del área,
     *   los exámenes con el mismo grupo_reporte van juntos bajo su título;
     * - un bloque por perfil, en la posición de su primer examen, con sus
     *   exámenes separados por área (dentro del perfil no se aplica grupo_reporte).
     */
    private function bloquesInforme(Solicitude $solicitude): array
    {
        $items = $solicitude->laboratorioItems
            ->filter(fn ($item) => $item->resultados->where('visible', true)->isNotEmpty() || filled($item->producto?->descripcion))
            ->values();

        $bloques = [];
        $indiceArea = [];
        $indicePerfil = [];
        foreach ($items as $posicion => $item) {
            if ($item->solicitud_laboratorio_perfil_id && $item->perfil) {
                $clave = $item->solicitud_laboratorio_perfil_id;
                if (! isset($indicePerfil[$clave])) {
                    $indicePerfil[$clave] = count($bloques);
                    // Nombre guardado en la solicitud: vale aunque el perfil ya no exista en el catálogo.
                    $bloques[] = ['tipo' => 'perfil', 'titulo' => $item->perfil->nombre, 'items' => []];
                }
                $bloques[$indicePerfil[$clave]]['items'][] = $item;

                continue;
            }

            $clave = $item->producto?->tipo_producto_id ?: 0;
            if (! isset($indiceArea[$clave])) {
                $indiceArea[$clave] = count($bloques);
                $bloques[] = ['tipo' => 'area', 'nombre' => $this->nombreArea($item), 'items' => []];
            }
            $bloques[$indiceArea[$clave]]['items'][] = ['item' => $item, 'posicion' => $posicion];
        }

        foreach ($indiceArea as $indice) {
            $bloques[$indice] = $this->bloqueArea($bloques[$indice]);
        }
        foreach ($indicePerfil as $indice) {
            $bloques[$indice] = $this->bloquePerfil($bloques[$indice]);
        }

        // Un perfil sin ningún resultado visible no se imprime (ni su título solo).
        return array_values(array_filter($bloques, fn ($bloque) => $bloque['tipo'] !== 'perfil' || $bloque['tiene_resultados']));
    }

    /**
     * Bloques del informe para la página pública de verificación: áreas y
     * perfiles con sus pruebas, sin descripciones.
     */
    private function bloquesVerificacion(Solicitude $solicitude): array
    {
        $pruebas = fn (array $pruebas) => array_values(array_filter(array_map(function ($prueba) {
            $filas = [];
            foreach ($prueba['filas'] as $fila) {
                if ($fila['tipo'] === 'subtitulo') {
                    $filas[] = ['tipo' => 'subtitulo', 'nombre' => $fila['nombre']];
                } elseif ($fila['tipo'] === 'resultado') {
                    $resultado = $fila['resultado'];
                    $filas[] = [
                        'tipo' => 'resultado',
                        'nombre' => $fila['nombre'],
                        'valor' => $resultado->valor,
                        'unidad' => $resultado->unidad,
                        'rango_referencia' => $resultado->rango_referencia,
                        'rango_referencia_html' => $resultado->rango_referencia_html,
                        'metodo' => $resultado->metodo,
                        'muestra' => $resultado->muestra,
                    ];
                }
            }

            // Un examen que solo tenía descripción no se muestra.
            return collect($filas)->contains('tipo', 'resultado') ? $filas : null;
        }, $pruebas)));
        $area = fn (array $area) => ['nombre' => $area['nombre'], 'pruebas' => $pruebas($area['pruebas'])];

        return collect($this->bloquesInforme($solicitude))
            ->map(fn ($bloque) => $bloque['tipo'] === 'perfil'
                ? ['tipo' => 'perfil', 'titulo' => $bloque['titulo'], 'areas' => array_map($area, $bloque['areas'])]
                : ['tipo' => 'area', ...$area($bloque)])
            ->filter(fn ($bloque) => $bloque['tipo'] === 'perfil' || $bloque['pruebas'] !== [])
            ->values()
            ->all();
    }

    private function nombreArea($item): string
    {
        return $item->producto?->tipoProducto?->nombre ?: 'LABORATORIO';
    }

    /**
     * Pruebas de un área: cada examen suelto, o un grupo_reporte con sus
     * exámenes (en la posición del primero, ordenados por orden_reporte, vacíos
     * al final, y luego por el orden de la solicitud).
     */
    private function bloqueArea(array $bloque): array
    {
        $secciones = [];
        $indiceGrupo = [];
        foreach ($bloque['items'] as $miembro) {
            $grupo = trim((string) $miembro['item']->producto?->grupo_reporte);
            if ($grupo === '') {
                $secciones[] = ['grupo' => null, 'miembros' => [$miembro]];

                continue;
            }
            $clave = mb_strtoupper($grupo);
            if (! isset($indiceGrupo[$clave])) {
                $indiceGrupo[$clave] = count($secciones);
                $secciones[] = ['grupo' => $grupo, 'miembros' => []];
            }
            $secciones[$indiceGrupo[$clave]]['miembros'][] = $miembro;
        }

        $pruebas = array_map(function ($seccion) {
            if ($seccion['grupo'] === null) {
                $item = $seccion['miembros'][0]['item'];
                $filas = $this->filasExamen($item);
                if (filled($item->producto?->descripcion)) {
                    $filas[] = ['tipo' => 'descripcion', 'html' => $item->producto->descripcion_html];
                }

                return $filas;
            }

            $miembros = collect($seccion['miembros'])
                ->sortBy(fn ($m) => [$m['item']->producto->orden_reporte === null ? 1 : 0, $m['item']->producto->orden_reporte ?? 0, $m['posicion']])
                ->pluck('item');
            $filas = [['tipo' => 'subtitulo', 'nombre' => mb_strtoupper($seccion['grupo'])]];
            foreach ($miembros as $item) {
                // Dentro de un grupo, cada parámetro es una fila (como antes); un
                // examen de un solo parámetro lleva el nombre del examen.
                foreach ($item->resultados->where('visible', true) as $resultado) {
                    $filas[] = ['tipo' => 'resultado', 'nombre' => $item->resultados->count() === 1 ? $item->producto_nombre : $resultado->nombre, 'resultado' => $resultado];
                }
            }
            // La descripción se imprime una vez solo si es idéntica en todos.
            $descripciones = $miembros->map(fn ($item) => (string) $item->producto?->descripcion)->unique();
            if ($descripciones->count() === 1 && filled($descripciones->first())) {
                $filas[] = ['tipo' => 'descripcion', 'html' => $miembros->first()->producto->descripcion_html];
            }

            return $filas;
        }, $secciones);

        return ['tipo' => 'area', 'nombre' => $bloque['nombre'], 'pruebas' => $this->conAgrupacion($pruebas)];
    }

    /**
     * Filas de un examen: con un solo parámetro, una fila con el nombre del
     * examen (en lugar del genérico «RESULTADO»); con varios, un subtítulo con
     * el nombre del examen y debajo sus resultados visibles. Sin resultados
     * visibles (solo descripción), el subtítulo si se pide.
     */
    private function filasExamen($item, bool $subtituloSiVacio = true): array
    {
        $visibles = $item->resultados->where('visible', true)->values();
        if ($item->resultados->count() === 1 && $visibles->isNotEmpty()) {
            return [['tipo' => 'resultado', 'nombre' => $item->producto_nombre, 'resultado' => $visibles->first()]];
        }
        if ($visibles->isEmpty()) {
            return $subtituloSiVacio ? [['tipo' => 'subtitulo', 'nombre' => $item->producto_nombre]] : [];
        }

        return [
            ['tipo' => 'subtitulo', 'nombre' => $item->producto_nombre],
            ...$visibles->map(fn ($resultado) => ['tipo' => 'resultado', 'nombre' => $resultado->nombre, 'resultado' => $resultado])->all(),
        ];
    }

    /**
     * Cada prueba lleva «junta» si cabe en una página: se imprime en una sola
     * fila (tabla anidada) y no se parte; si es más larga, sus filas van sueltas
     * y el subtítulo queda pegado a la primera.
     */
    private function conAgrupacion(array $pruebas): array
    {
        return array_values(array_map(fn ($filas) => [
            'filas' => $filas,
            'junta' => count($filas) <= self::FILAS_PRUEBA_SIN_PARTIR,
        ], array_filter($pruebas)));
    }

    /**
     * Bloque de un perfil, en el orden guardado: los exámenes se separan por
     * área cada vez que el área cambia, con el formato de área (nombre centrado
     * y títulos de columna). Las descripciones van al pie, sin repetir.
     */
    private function bloquePerfil(array $bloque): array
    {
        $areas = [];
        $descripciones = [];
        foreach ($bloque['items'] as $item) {
            if (filled($item->producto?->descripcion)) {
                $descripciones[(string) $item->producto->descripcion] = $item->producto->descripcion_html;
            }
            if ($item->resultados->where('visible', true)->isEmpty()) {
                continue;
            }
            $area = $this->nombreArea($item);
            if (! $areas || $areas[count($areas) - 1]['nombre'] !== $area) {
                $areas[] = ['nombre' => $area, 'pruebas' => []];
            }
            $areas[count($areas) - 1]['pruebas'][] = $this->filasExamen($item, false);
        }
        $areas = array_map(fn ($area) => ['nombre' => $area['nombre'], 'pruebas' => $this->conAgrupacion($area['pruebas'])], $areas);
        $filas = collect($areas)->sum(fn ($area) => 1 + collect($area['pruebas'])->sum(fn ($prueba) => count($prueba['filas'])));

        return [
            'tipo' => 'perfil',
            'titulo' => $bloque['titulo'],
            'areas' => $areas,
            'descripciones' => array_values($descripciones),
            'tiene_resultados' => $areas !== [],
            // Corto: cabe en una página y se imprime sin partir; largo: repite el título en cada página.
            'corto' => $filas <= self::FILAS_PERFIL_SIN_PARTIR,
        ];
    }

    /**
     * Guarda el plan de planLaboratorios(). Las filas de perfil y los ítems que ya
     * existían se reutilizan (por perfil y por examen), así un examen conserva sus
     * resultados aunque pase de suelto a un perfil o al revés. Lo que sobra se
     * quita con soft delete.
     */
    private function syncLaboratorios(Solicitude $solicitud, array $plan, $valores): void
    {
        $itemsAnteriores = $solicitud->laboratorioItems()->with('resultados')->get()->keyBy('producto_id');
        $perfilesAnteriores = $solicitud->laboratorioPerfiles()->get()->keyBy('perfil_producto_id');
        $posicion = 0;

        foreach ($plan['filas'] as $fila) {
            $perfilSolicitud = null;
            if ($fila['perfil']) {
                // El orden del perfil es el de su primer examen: así se mezcla con los sueltos.
                $datosPerfil = [
                    'perfil_producto_id' => $fila['perfil']->id,
                    'nombre' => $fila['perfil']->nombre,
                    'precio' => $fila['perfil']->precio,
                    'orden' => ($posicion + 1) * 10,
                ];
                $perfilSolicitud = $perfilesAnteriores->pull($fila['perfil']->id);
                $perfilSolicitud
                    ? $perfilSolicitud->update($datosPerfil)
                    : $perfilSolicitud = $solicitud->laboratorioPerfiles()->create($datosPerfil);
            }
            foreach ($fila['examenes'] as $producto) {
                $this->guardarExamen($solicitud, $producto, $perfilSolicitud, ++$posicion * 10, $itemsAnteriores, $valores);
            }
        }

        $perfilesAnteriores->each->delete();
        $itemsAnteriores->each(function ($item) {
            $item->resultados->each->delete();
            $item->delete();
        });
    }

    private function guardarExamen(Solicitude $solicitud, Producto $producto, $perfilSolicitud, int $orden, $itemsAnteriores, $valores): void
    {
        $datosItem = [
            'producto_id' => $producto->id,
            'solicitud_laboratorio_perfil_id' => $perfilSolicitud?->id,
            'producto_nombre' => $producto->nombre,
            'precio' => $perfilSolicitud ? 0 : $producto->precio,
            'orden' => $orden,
        ];
        $item = $itemsAnteriores->pull($producto->id);
        if ($item) {
            $item->update($datosItem);
        } else {
            $item = $solicitud->laboratorioItems()->create($datosItem);
        }
        $resultadosAnteriores = $item->resultados->keyBy('producto_laboratorio_dato_id');

        foreach ($producto->laboratorioDatos as $dato) {
            $valorConfigurado = $valores->get($dato->id, []);
            $datosResultado = [
                'producto_laboratorio_dato_id' => $dato->id,
                'nombre' => $dato->nombre,
                'nombre_variable' => $dato->nombre_variable,
                'unidad' => $dato->unidad,
                'metodo' => $dato->metodo,
                'muestra' => $dato->muestra,
                'rango_referencia' => $dato->rango_referencia,
                'formula' => $dato->formula?->formula,
                'valor' => $valorConfigurado['valor'] ?? null,
                'orden' => $dato->orden,
                'visible' => array_key_exists('visible', $valorConfigurado)
                    ? (bool) $valorConfigurado['visible']
                    : $dato->visible,
            ];
            $resultado = $resultadosAnteriores->pull($dato->id);
            $resultado ? $resultado->update($datosResultado) : $item->resultados()->create($datosResultado);
        }
        $resultadosAnteriores->each->delete();
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
