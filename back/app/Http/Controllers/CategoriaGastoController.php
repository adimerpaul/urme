<?php

namespace App\Http\Controllers;

use App\Models\TipoProductoPadre;
use Illuminate\Http\Request;

/** Categorías (tipo_producto_padres) y subcategorías (tipo_productos) para los gastos. */
class CategoriaGastoController extends Controller
{
    /** Quien registra o edita gastos en Ventas o en las cajas necesita la lista. */
    private const PERMISOS = [
        'Crear Ventas',
        'Crear Caja General', 'Editar Caja General',
        'Crear Caja Administrativa', 'Editar Caja Administrativa',
    ];

    public function index(Request $request)
    {
        $usuario = $request->user();
        abort_unless(collect(self::PERMISOS)->contains(fn ($permiso) => $usuario->can($permiso)), 403, 'No tiene permiso para realizar esta acción');

        // Solo activas (SoftDeletes excluye las borradas), ordenadas por orden y nombre.
        $categorias = TipoProductoPadre::query()
            ->with(['tipos' => fn ($query) => $query->orderBy('orden')->orderBy('nombre')->select('id', 'tipo_producto_padre_id', 'nombre')])
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return response()->json($categorias->map(fn ($categoria) => [
            'id' => $categoria->id,
            'nombre' => $categoria->nombre,
            'subcategorias' => $categoria->tipos->map(fn ($tipo) => ['id' => $tipo->id, 'nombre' => $tipo->nombre])->values(),
        ]));
    }
}
