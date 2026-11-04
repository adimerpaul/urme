<?php

namespace App\Support;

use App\Models\TipoProducto;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Validación de la categoría y subcategoría de un gasto (Ventas y cajas). */
class CategoriaGasto
{
    /**
     * Valida tipo_producto_padre_id (categoría) y tipo_producto_id (subcategoría):
     * solo categorías no borradas, y la subcategoría debe ser de la categoría
     * elegida. Devuelve los dos ids (la subcategoría puede ser null).
     *
     * @param  bool  $obligatoria  false solo al editar un gasto antiguo sin categoría.
     */
    public static function validar(Request $request, bool $obligatoria = true): array
    {
        $datos = $request->validate([
            'tipo_producto_padre_id' => [$obligatoria ? 'required' : 'nullable', 'integer', 'exists:tipo_producto_padres,id,deleted_at,NULL'],
            'tipo_producto_id' => ['nullable', 'integer', 'exists:tipo_productos,id,deleted_at,NULL'],
        ], [
            'tipo_producto_padre_id.required' => 'Seleccione la categoría del gasto.',
            'tipo_producto_padre_id.exists' => 'La categoría seleccionada no existe o fue eliminada.',
            'tipo_producto_id.exists' => 'La subcategoría seleccionada no existe o fue eliminada.',
        ]);
        $padre = $datos['tipo_producto_padre_id'] ?? null;
        $sub = $datos['tipo_producto_id'] ?? null;

        if ($sub && ! $padre) {
            throw ValidationException::withMessages(['tipo_producto_padre_id' => 'Seleccione la categoría de la subcategoría elegida.']);
        }
        if ($sub && ! TipoProducto::whereKey($sub)->where('tipo_producto_padre_id', $padre)->exists()) {
            throw ValidationException::withMessages(['tipo_producto_id' => 'La subcategoría no pertenece a la categoría elegida.']);
        }

        return ['tipo_producto_padre_id' => $padre, 'tipo_producto_id' => $sub];
    }
}
