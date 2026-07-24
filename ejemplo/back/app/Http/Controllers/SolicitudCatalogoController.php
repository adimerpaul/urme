<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Doctor;
use App\Models\Establecimiento;
use App\Models\Solicitude;
use App\Models\SolicitudCorrelativo;
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
            'agrupaciones' => \App\Models\Agrupacion::with('prestaciones:id')
                ->where('activo', true)
                ->orderBy('orden')
                ->get(),
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

    /**
     * GET /solicitudes-siguiente-codigo?tipo_atencion=SI
     * Devuelve la próxima numeración del mes desde solicitud_correlativos.
     * Requiere el permiso "Generar código solicitud".
     */
    public function siguienteCodigo(Request $request)
    {
        $user = $request->user();
        $autorizado = $user && ($user->role === 'Administrador' || $user->can('Generar código solicitud'));

        if (! $autorizado) {
            return response()->json(['message' => 'No tiene permiso para generar el código.'], 403);
        }

        $tipo = $request->query('tipo_atencion', 'SI');

        return response()->json([
            'numero' => SolicitudCorrelativo::siguiente($tipo),
        ]);
    }

    private function siguienteCodigoSugerido(string $tipo, Request $request): int
    {
        // Si ya existe correlativo para el mes en curso, esa es la fuente de verdad.
        $correlativo = SolicitudCorrelativo::where('tipo_atencion', $tipo)
            ->where('anio', (int) now()->format('Y'))
            ->where('mes', (int) now()->format('m'))
            ->first();

        if ($correlativo) {
            return $correlativo->ultimo_numero + 1;
        }

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
