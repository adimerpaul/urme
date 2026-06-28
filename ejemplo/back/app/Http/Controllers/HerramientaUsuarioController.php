<?php

namespace App\Http\Controllers;

use App\Models\HerramientaUsuario;
use Illuminate\Http\Request;

class HerramientaUsuarioController extends Controller
{
    public function index()
    {
        $rows = HerramientaUsuario::all()->keyBy('nombre');

        return response()->json([
            'fecha_inicio_registro' => $rows['fecha_inicio_registro']?->valor ?? null,
            'fecha_fin_registro'    => $rows['fecha_fin_registro']?->valor ?? null,
            'registro_habilitado'   => HerramientaUsuario::registroHabilitado(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'fecha_inicio_registro' => 'nullable|date_format:Y-m-d\TH:i',
            'fecha_fin_registro'    => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:fecha_inicio_registro',
        ]);

        foreach ($data as $nombre => $valor) {
            HerramientaUsuario::updateOrCreate(
                ['nombre' => $nombre],
                ['valor'  => $valor]
            );
        }

        return $this->index();
    }
}
