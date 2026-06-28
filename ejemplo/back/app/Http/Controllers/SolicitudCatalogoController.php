<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Doctor;
use App\Models\Establecimiento;
use App\Models\Solicitude;
use App\Models\UnidadSolicitante;
use Illuminate\Http\Request;

class SolicitudCatalogoController extends Controller
{
    public function create(Request $request)
    {
        return response()->json([
            'doctores' => Doctor::with('establecimiento')
                ->orderBy('id', 'desc')
                ->get(),
            'establecimientos' => Establecimiento::with('servicios')
                ->orderBy('id', 'desc')
                ->get()
                ->each(function ($establecimiento) {
                    $establecimiento->servicio_ids = $establecimiento->servicios->pluck('id');
                }),
            'unidades_solicitantes' => UnidadSolicitante::orderBy('nombre')->get(),
            'areas' => $this->areasParaCrearSolicitud($request),
            'codigos_sugeridos' => [
                'SI' => $this->siguienteCodigoSugerido('SI', $request),
                'NO' => $this->siguienteCodigoSugerido('NO', $request),
            ],
        ]);
    }

    private function areasParaCrearSolicitud(Request $request)
    {
        $user = $request->user();
        $query = Area::with('servicios.tiposMuestra')->orderBy('id', 'asc');

        if ($user->role === 'Administrador') {
            return $query->get();
        }

        $area = $user->area;
        $idBiologiaMolecular = 7;

        if ($area && $area->id == $idBiologiaMolecular) {
            return $query->where('id', $idBiologiaMolecular)->get();
        }

        return $query->where('id', '<>', $idBiologiaMolecular)->get();
    }

    private function siguienteCodigoSugerido(string $tipo, Request $request): int
    {
        $fechaBase = now()->toDateString();
        $timestamp = strtotime($fechaBase);

        $anio = date('Y', $timestamp);
        $mes = date('m', $timestamp);
        $establecimientoId = $request->user() && $request->user()->establecimiento
            ? $request->user()->establecimiento->id
            : null;

        $query = Solicitude::query()
            ->where('tipo_atencion', $tipo)
            ->whereYear('fecha_creacion', $anio)
            ->whereMonth('fecha_creacion', $mes)
            ->whereNotNull('codigo');

        if ($establecimientoId) {
            $query->where('establecimiento_origen_id', $establecimientoId);
        }

        if ($tipo !== 'SI') {
            $query->whereDate('fecha_creacion', date('Y-m-d', $timestamp));
        }

        $ultimoCodigo = $query->where('codigo', '<', 10000)->max('codigo');

        return $ultimoCodigo ? ((int) $ultimoCodigo + 1) : 1;
    }
}
