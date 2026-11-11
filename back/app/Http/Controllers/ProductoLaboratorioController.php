<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoLaboratorioDato;
use App\Models\ProductoLaboratorioFormula;
use App\Models\ProductoLaboratorioValidacion;
use App\Support\DescripcionHtml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoLaboratorioController extends Controller
{
    public function show(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, ['Ver Productos', 'Editar Productos']);
        $this->ensureLaboratorio($producto);

        $producto->load([
            'laboratorioDatos.formula',
            'laboratorioDatos.opciones',
            'laboratorioFormulas',
            'laboratorioValidaciones',
        ]);
        if (config('laboratorio.perfiles_habilitado')) {
            $producto->setAttribute('perfil', $this->datosPerfil($producto));
        }

        return response()->json($producto);
    }

    /**
     * Marca o desmarca el producto como perfil y guarda sus exámenes en el orden
     * recibido. Con es_perfil = false se quitan todos sus exámenes.
     */
    public function updatePerfil(Request $request, Producto $producto)
    {
        abort_unless(config('laboratorio.perfiles_habilitado'), 404);
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);

        $data = $request->validate([
            'es_perfil' => ['required', 'boolean'],
            'producto_ids' => ['exclude_unless:es_perfil,true', 'required', 'array', 'min:2'],
            'producto_ids.*' => ['integer', 'distinct'],
        ], [
            'producto_ids.required' => 'Agregue los exámenes del perfil.',
            'producto_ids.min' => 'Un perfil debe tener al menos 2 exámenes.',
            'producto_ids.*.distinct' => 'Un examen no puede repetirse en el perfil.',
        ]);

        if (! $data['es_perfil']) {
            DB::transaction(function () use ($producto) {
                $producto->perfilItems()->get()->each->delete();
                $producto->update(['es_perfil' => false]);
            });

            return response()->json($this->datosPerfil($producto->fresh()));
        }

        $this->validarPuedeSerPerfil($producto);
        $ids = array_map('intval', $data['producto_ids']);
        $this->validarExamenes($producto, $ids);

        DB::transaction(function () use ($producto, $ids) {
            $producto->update(['es_perfil' => true]);
            // Se conservan las filas que siguen para no perder su historial de auditoría.
            $existentes = $producto->perfilItems()->get()->keyBy('producto_id');
            foreach ($ids as $indice => $id) {
                $orden = ($indice + 1) * 10;
                $item = $existentes->pull($id);
                if (! $item) {
                    $producto->perfilItems()->create(['producto_id' => $id, 'orden' => $orden]);
                } elseif ((int) $item->orden !== $orden) {
                    $item->update(['orden' => $orden]);
                }
            }
            $existentes->each->delete();
        });

        return response()->json($this->datosPerfil($producto->fresh()));
    }

    /**
     * Agrupación en el informe impreso. El grupo se guarda tal como se escribe
     * (solo trim); el informe lo muestra en mayúsculas.
     */
    public function updateReporte(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);

        $data = $request->validate([
            'grupo_reporte' => 'nullable|string|max:100',
            'orden_reporte' => 'nullable|integer|min:0|max:65535',
        ]);
        $grupo = trim($data['grupo_reporte'] ?? '');

        $producto->update([
            'grupo_reporte' => $grupo !== '' ? $grupo : null,
            'orden_reporte' => $data['orden_reporte'] ?? null,
        ]);

        return response()->json($producto->only(['id', 'grupo_reporte', 'orden_reporte']));
    }

    public function storeDato(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $this->ensureNoPerfil($producto);
        $data = $this->validateDato($request, $producto);
        $opciones = $data['opciones'];
        unset($data['opciones']);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioDatos()->max('orden')) + 10;

        $dato = ProductoLaboratorioDato::create($data);
        $this->sincronizarOpciones($dato, $opciones);

        return response()->json($dato->load('opciones'), 201);
    }

    public function updateDato(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);
        $data = $this->validateDato($request, $dato->producto, $dato);
        $opciones = $data['opciones'];
        unset($data['opciones']);

        $dato->update($data);
        $this->sincronizarOpciones($dato, $opciones);
        $dato->formula?->update([
            'nombre' => $dato->nombre,
            'nombre_variable' => $dato->nombre_variable,
            'unidad' => $dato->unidad,
            'visible' => $dato->visible,
        ]);

        return response()->json($dato->load('opciones'));
    }

    public function destroyDato(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);

        $usada = $dato->producto->laboratorioFormulas()
            ->where('formula', 'regexp', '(^|[^a-zA-Z0-9_])'.$dato->nombre_variable.'([^a-zA-Z0-9_]|$)')
            ->exists();

        if ($usada) {
            return response()->json([
                'message' => 'No se puede eliminar: la variable está utilizada en una fórmula.',
            ], 422);
        }

        $dato->formula?->delete();
        $dato->delete();

        return response()->json(['message' => 'Dato eliminado']);
    }

    public function storeDatoFormula(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);
        $data = $this->validateDatoFormula($request, $dato);

        $formula = $dato->formula()->withTrashed()->first() ?? new ProductoLaboratorioFormula;
        $formula->fill([
            ...$data,
            'producto_id' => $dato->producto_id,
            'producto_laboratorio_dato_id' => $dato->id,
            'nombre' => $dato->nombre,
            'nombre_variable' => $dato->nombre_variable,
            'unidad' => $dato->unidad,
            'orden' => $dato->orden,
            'visible' => $dato->visible,
        ]);
        $formula->deleted_at = null;
        $formula->save();

        return response()->json($formula, 201);
    }

    public function reorderDatos(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $currentIds = $producto->laboratorioDatos()->pluck('id')->sort()->values();
        $requestedIds = collect($validated['ids'])->sort()->values();

        if ($currentIds->all() !== $requestedIds->all()) {
            throw ValidationException::withMessages([
                'ids' => 'La lista de datos no corresponde al laboratorio seleccionado.',
            ]);
        }

        DB::transaction(function () use ($validated, $producto) {
            foreach ($validated['ids'] as $index => $id) {
                $producto->laboratorioDatos()->whereKey($id)->update([
                    'orden' => ($index + 1) * 10,
                ]);
            }
        });

        return response()->json(['message' => 'Orden actualizado']);
    }

    public function storeFormula(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $this->ensureNoPerfil($producto);
        $data = $this->validateFormula($request, $producto);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioFormulas()->max('orden')) + 10;

        return response()->json(ProductoLaboratorioFormula::create($data), 201);
    }

    public function updateFormula(Request $request, ProductoLaboratorioFormula $formula)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($formula->producto);
        if ($formula->dato) {
            $formula->update($this->validateDatoFormula($request, $formula->dato));
        } else {
            $formula->update($this->validateFormula($request, $formula->producto, $formula));
        }

        return response()->json($formula);
    }

    public function destroyFormula(Request $request, ProductoLaboratorioFormula $formula)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($formula->producto);
        $formula->delete();

        return response()->json(['message' => 'Fórmula eliminada']);
    }

    /**
     * Deja la lista de opciones del dato igual a la recibida, conservando las
     * filas que ya existían para no perder su historial de auditoría.
     */
    private function sincronizarOpciones(ProductoLaboratorioDato $dato, array $valores): void
    {
        $existentes = $dato->opciones()->get()->keyBy('valor');

        foreach ($valores as $indice => $valor) {
            $opcion = $existentes->get($valor);
            if ($opcion) {
                $opcion->update(['orden' => $indice]);
                $existentes->forget($valor);
            } else {
                $dato->opciones()->create(['valor' => $valor, 'orden' => $indice]);
            }
        }

        // Lo que quedó en $existentes ya no está en la lista enviada.
        $existentes->each->delete();
    }

    private function validateDato(
        Request $request,
        Producto $producto,
        ?ProductoLaboratorioDato $dato = null
    ): array {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'nombre_variable' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('producto_laboratorio_datos', 'nombre_variable')
                    ->where('producto_id', $producto->id)
                    ->whereNull('deleted_at')
                    ->ignore($dato?->id),
            ],
            'unidad' => 'nullable|string|max:100',
            'metodo' => 'nullable|string|max:100',
            'muestra' => 'nullable|string|max:100',
            'rango_referencia' => 'nullable|string|max:1000000',
            'valor_defecto' => 'nullable|string|max:255',
            'opciones' => 'nullable|array|max:50',
            'opciones.*' => 'required|string|max:255',
        ]);

        $data['nombre'] = mb_strtoupper(trim($data['nombre']));
        $data['nombre_variable'] = mb_strtolower(trim($data['nombre_variable']));
        $data['unidad'] = isset($data['unidad']) ? trim($data['unidad']) : null;
        $data['metodo'] = isset($data['metodo']) && trim($data['metodo']) !== ''
            ? mb_strtoupper(trim($data['metodo']))
            : null;
        $data['muestra'] = isset($data['muestra']) && trim($data['muestra']) !== ''
            ? mb_strtoupper(trim($data['muestra']))
            : null;
        // El rango llega como HTML del editor: se limpia y, si no tiene texto
        // (el editor deja "<br>" o "<p></p>"), se guarda como nulo.
        $rango = trim($data['rango_referencia'] ?? '');
        $textoRango = str_replace("\u{00A0}", ' ', html_entity_decode(strip_tags($rango), ENT_QUOTES | ENT_HTML5));
        $data['rango_referencia'] = trim($textoRango) !== '' ? DescripcionHtml::guardar($rango) : null;
        $data['valor_defecto'] = isset($data['valor_defecto']) && trim($data['valor_defecto']) !== ''
            ? mb_strtoupper(trim($data['valor_defecto']))
            : null;

        // Las opciones se normalizan y se quitan duplicados y vacíos.
        $data['opciones'] = collect($data['opciones'] ?? [])
            ->map(fn ($valor) => mb_strtoupper(trim($valor)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Si el dato tiene lista cerrada, el valor por defecto debe salir de
        // ella: si no, el resultado arrancaría con un valor que el operador no
        // puede volver a elegir.
        if ($data['opciones'] && $data['valor_defecto'] && ! in_array($data['valor_defecto'], $data['opciones'], true)) {
            throw ValidationException::withMessages([
                'valor_defecto' => 'El valor por defecto debe ser una de las opciones de la lista.',
            ]);
        }

        if ($producto->laboratorioFormulas()
            ->when($dato, fn ($query) => $query->where('producto_laboratorio_dato_id', '!=', $dato->id))
            ->where('nombre_variable', $data['nombre_variable'])
            ->exists()) {
            throw ValidationException::withMessages([
                'nombre_variable' => 'La variable ya está utilizada como resultado de una fórmula.',
            ]);
        }

        return $data;
    }

    private function validateFormula(
        Request $request,
        Producto $producto,
        ?ProductoLaboratorioFormula $formula = null
    ): array {
        $data = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'nombre_variable' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('producto_laboratorio_formulas', 'nombre_variable')
                    ->where('producto_id', $producto->id)
                    ->whereNull('deleted_at')
                    ->ignore($formula?->id),
            ],
            'formula' => ['required', 'string', 'max:2000', 'regex:/^[a-zA-Z0-9_+\-*\/().\s]+$/'],
            'unidad' => 'nullable|string|max:100',
            'visible' => 'required|boolean',
        ]);

        $variables = $producto->laboratorioDatos()->pluck('nombre_variable')
            ->merge(
                $producto->laboratorioFormulas()
                    ->when($formula, fn ($query) => $query->whereKeyNot($formula->id))
                    ->pluck('nombre_variable')
            )
            ->unique();

        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $data['formula'], $matches);
        $unknown = collect($matches[0])->map(fn ($value) => mb_strtolower($value))
            ->diff($variables)
            ->unique()
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'formula' => 'Variables desconocidas: '.$unknown->join(', '),
            ]);
        }

        $data['nombre'] = isset($data['nombre']) ? mb_strtoupper(trim($data['nombre'])) : null;
        $data['nombre_variable'] = mb_strtolower(trim($data['nombre_variable']));
        $data['formula'] = mb_strtolower(trim($data['formula']));
        $data['unidad'] = isset($data['unidad']) ? trim($data['unidad']) : null;

        if ($producto->laboratorioDatos()->where('nombre_variable', $data['nombre_variable'])->exists()) {
            throw ValidationException::withMessages([
                'nombre_variable' => 'La variable ya está utilizada por un dato del laboratorio.',
            ]);
        }

        return $data;
    }

    private function validateDatoFormula(Request $request, ProductoLaboratorioDato $dato): array
    {
        $data = $request->validate([
            'formula' => ['required', 'string', 'max:2000', 'regex:/^[a-zA-Z0-9_+\-*\/().\s]+$/'],
        ]);

        $variables = $dato->producto->laboratorioDatos()
            ->whereKeyNot($dato->id)
            ->pluck('nombre_variable');

        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $data['formula'], $matches);
        $unknown = collect($matches[0])
            ->map(fn ($value) => mb_strtolower($value))
            ->diff($variables)
            ->unique()
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'formula' => 'Variables desconocidas: '.$unknown->join(', '),
            ]);
        }

        $data['formula'] = mb_strtolower(trim($data['formula']));

        return $data;
    }

    // ── Validaciones (mensajes de control) ────────────────────────

    public function storeValidacion(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $this->ensureNoPerfil($producto);
        $data = $this->validateValidacion($request, $producto);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioValidaciones()->max('orden')) + 10;

        return response()->json(ProductoLaboratorioValidacion::create($data), 201);
    }

    public function updateValidacion(Request $request, ProductoLaboratorioValidacion $validacion)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($validacion->producto);
        $validacion->update($this->validateValidacion($request, $validacion->producto));

        return response()->json($validacion);
    }

    public function destroyValidacion(Request $request, ProductoLaboratorioValidacion $validacion)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($validacion->producto);
        $validacion->delete();

        return response()->json(['message' => 'Mensaje eliminado']);
    }

    private function validateValidacion(Request $request, Producto $producto): array
    {
        $data = $request->validate([
            'expresion' => ['required', 'string', 'max:2000', 'regex:/^[a-zA-Z0-9_+\-*\/().\s]+$/'],
            'operador' => ['required', Rule::in(['=', '!=', '>', '>=', '<', '<=', 'entre'])],
            'valor' => ['required', 'numeric'],
            'valor_hasta' => ['nullable', 'numeric', 'required_if:operador,entre'],
            'mensaje' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $variables = $producto->laboratorioDatos()->pluck('nombre_variable');
        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $data['expresion'], $matches);
        $unknown = collect($matches[0])
            ->map(fn ($value) => mb_strtolower($value))
            ->diff($variables)
            ->unique()
            ->values();

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'expresion' => 'Variables desconocidas: '.$unknown->join(', '),
            ]);
        }

        if ($data['operador'] === 'entre' && (float) $data['valor_hasta'] < (float) $data['valor']) {
            throw ValidationException::withMessages([
                'valor_hasta' => 'El valor final debe ser mayor o igual al inicial.',
            ]);
        }

        $data['expresion'] = mb_strtolower(trim($data['expresion']));
        if ($data['operador'] !== 'entre') {
            $data['valor_hasta'] = null;
        }

        return $data;
    }

    /** Composición del perfil (o perfiles que incluyen al examen) para la pantalla del catálogo. */
    private function datosPerfil(Producto $producto): array
    {
        $items = $producto->perfilItems()
            ->with(['producto' => fn ($query) => $query->withTrashed()->with('tipoProducto:id,nombre,es_laboratorio')])
            ->get();

        return [
            'es_perfil' => (bool) $producto->es_perfil,
            'vendible' => $producto->esPerfilVendible(),
            'examenes' => $items->map(fn ($item) => [
                'id' => $item->producto_id,
                'codigo' => $item->producto?->codigo,
                'nombre' => $item->producto?->nombre,
                'precio' => $item->producto?->precio,
                'area' => $item->producto?->tipoProducto?->nombre,
                'activo' => Producto::examenActivo($item->producto),
            ])->values(),
            'perfiles' => $producto->perfiles()->orderBy('nombre')->get(['productos.id', 'productos.nombre'])
                ->map(fn ($perfil) => ['id' => $perfil->id, 'nombre' => $perfil->nombre]),
        ];
    }

    /** Un perfil no tiene parámetros, fórmulas, validaciones ni reactivos propios, ni integra otro perfil. */
    private function validarPuedeSerPerfil(Producto $producto): void
    {
        $propios = collect([
            'parámetros' => $producto->laboratorioDatos()->exists(),
            'fórmulas' => $producto->laboratorioFormulas()->exists(),
            'mensajes de validación' => $producto->laboratorioValidaciones()->exists(),
            'reactivos' => $producto->reactivoServicios()->exists(),
        ])->filter()->keys();
        if ($propios->isNotEmpty()) {
            throw ValidationException::withMessages([
                'es_perfil' => "{$producto->nombre} no puede ser un perfil: tiene ".$propios->join(', ', ' y ')
                    .' propios. Un perfil solo agrupa exámenes; quítelos primero.',
            ]);
        }

        $perfiles = $producto->perfiles()->pluck('nombre');
        if ($perfiles->isNotEmpty()) {
            throw ValidationException::withMessages([
                'es_perfil' => "{$producto->nombre} no puede ser un perfil: forma parte del perfil "
                    .$perfiles->implode(', ').'.',
            ]);
        }
    }

    /** Los exámenes deben existir, estar activos, ser de laboratorio y no ser perfiles. */
    private function validarExamenes(Producto $perfil, array $ids): void
    {
        $examenes = Producto::withTrashed()->with('tipoProducto')->whereIn('id', $ids)->get()->keyBy('id');
        $errores = [];
        foreach ($ids as $id) {
            $examen = $examenes->get($id);
            if ($id === $perfil->id) {
                $errores[] = 'Un perfil no puede incluirse a sí mismo.';
            } elseif (! $examen) {
                $errores[] = "El examen #{$id} no existe.";
            } elseif ($examen->trashed()) {
                $errores[] = "{$examen->nombre} está dado de baja.";
            } elseif ($examen->es_perfil) {
                $errores[] = "{$examen->nombre} es un perfil: un perfil no puede incluir otro perfil.";
            } elseif (! $examen->tipoProducto?->es_laboratorio) {
                $errores[] = "{$examen->nombre} no es un examen de laboratorio.";
            }
        }

        if ($errores) {
            throw ValidationException::withMessages(['producto_ids' => $errores]);
        }
    }

    /** Defensa también con los perfiles apagados: un perfil no lleva configuración propia. */
    private function ensureNoPerfil(Producto $producto): void
    {
        abort_if(
            $producto->es_perfil,
            422,
            'Un perfil no tiene parámetros, fórmulas ni mensajes de validación propios: se configuran en sus exámenes.'
        );
    }

    private function ensureLaboratorio(Producto $producto): void
    {
        // Cualquier área marcada como laboratorio sirve (HEMATOLOGIA,
        // UROANALISIS, …), no solo el tipo genérico LABORATORIOS.
        abort_unless(
            $producto->tipoProducto()->where('es_laboratorio', true)->exists(),
            422,
            'El producto seleccionado no pertenece a un área de laboratorio.'
        );
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
