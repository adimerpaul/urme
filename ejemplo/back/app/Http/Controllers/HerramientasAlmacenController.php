<?php

namespace App\Http\Controllers;

use App\Models\HerramientaAlmacen;
use Illuminate\Http\Request;

class HerramientasAlmacenController extends Controller
{
    public function index()
    {
        $rows = HerramientaAlmacen::all()->keyBy('nombre');

        return response()->json([
            'fecha_inicio_pedido_almacen' => $rows['fecha_inicio_pedido_almacen']?->valor ?? null,
            'fecha_fin_pedido_almacen'    => $rows['fecha_fin_pedido_almacen']?->valor ?? null,
            'pedidos_habilitados'         => HerramientaAlmacen::pedidosHabilitados(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio_pedido_almacen' => 'nullable|date',
            'fecha_fin_pedido_almacen'    => 'nullable|date|after_or_equal:fecha_inicio_pedido_almacen',
        ]);

        foreach ($data as $nombre => $valor) {
            HerramientaAlmacen::updateOrCreate(
                ['nombre' => $nombre],
                ['valor'  => $valor]
            );
        }

        return $this->index();
    }
}
