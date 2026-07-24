<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoLaboratorioDato;
use App\Models\ProductoLaboratorioFormula;
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

        return response()->json($producto->load(['laboratorioDatos.formula', 'laboratorioFormulas']));
    }

    public function storeDato(Request $request, Producto $producto)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($producto);
        $data = $this->validateDato($request, $producto);
        $data['producto_id'] = $producto->id;
        $data['orden'] = ((int) $producto->laboratorioDatos()->max('orden')) + 10;

        return response()->json(ProductoLaboratorioDato::create($data), 201);
    }

    public function updateDato(Request $request, ProductoLaboratorioDato $dato)
    {
        $this->authorizeAny($request, 'Editar Productos');
        $this->ensureLaboratorio($dato->producto);
        $dato->update($this->validateDato($request, $dato->producto, $dato));
        $dato->formula?->update([
            'nombre' => $dato->nombre,
            'nombre_variable' => $dato->nombre_variable,
            'unidad' => $dato->unidad,
            'visible' => $dato->visible,
        ]);

        return response()->json($dato);
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
            'rango_referencia' => 'nullable|string|max:2000',
            'visible' => 'required|boolean',
        ]);

        $data['nombre'] = mb_strtoupper(trim($data['nombre']));
        $data['nombre_variable'] = mb_strtolower(trim($data['nombre_variable']));
        $data['unidad'] = isset($data['unidad']) ? trim($data['unidad']) : null;
        $data['rango_referencia'] = isset($data['rango_referencia'])
            ? mb_strtoupper(trim($data['rango_referencia']))
            : null;

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

    private function ensureLaboratorio(Producto $producto): void
    {
        abort_unless(
            $producto->tipoProducto()->where('nombre', 'LABORATORIOS')->exists(),
            422,
            'El producto seleccionado no pertenece al tipo LABORATORIOS.'
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
