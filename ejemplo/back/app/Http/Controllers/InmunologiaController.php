<?php

namespace App\Http\Controllers;

use App\Models\Formularios;
use App\Models\ServicioSolicitude;
use App\Models\Solicitude;
use App\Models\SolicitudeFormulario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InmunologiaController extends Controller
{
    /**
     * Devuelve:
     * - solicitud
     * - formularios disponibles (base) del área
     * - formularios ya agregados a la solicitud
     */
    public function dashboard(Request $request, $solicitudeId)
    {
        $areaId = (int) $request->get('area_id', 0);
        if ($areaId <= 0) abort(422, 'area_id es requerido');

        $solicitud = Solicitude::with(['paciente', 'doctor','servicios'])->findOrFail($solicitudeId);

        $disponibles = Formularios::where('area_id', $areaId)
            ->orderBy('nombre')
            ->get();

        $seleccionados = SolicitudeFormulario::with(['formulario', 'area'])
            ->where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaId)
            ->orderBy('id', 'desc')
            ->get();

        $soliditude = Solicitude::find($solicitudeId);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();
        $soliditude->save();

        return response()->json([
            'solicitud'      => $solicitud,
            'disponibles'    => $disponibles,
            'seleccionados'  => $seleccionados,
        ]);
    }

    /**
     * Agregar un formulario base a la solicitud:
     * copia nombre/html desde formularios -> solicitude_formularios
     */
    public function add(Request $request, $solicitudeId)
    {
        $data = $request->validate([
            'formulario_id' => 'required|exists:formularios,id',
            'area_id'       => 'required|exists:areas,id',
        ]);

        $formulario = Formularios::where('id', $data['formulario_id'])
            ->where('area_id', $data['area_id'])
            ->firstOrFail();

        $row = SolicitudeFormulario::updateOrCreate(
            [
                'solicitude_id' => $solicitudeId,
                'formulario_id' => $formulario->id,
                'area_id'       => $data['area_id'],
            ],
            [
                'nombre' => $formulario->nombre,
                'html'   => $formulario->html,
                'user_id' => $request->user()->id
            ]
        );
        $areaIdHemato = 6;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdHemato)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);

        return response()->json($row->load(['formulario', 'area']));
    }

    /**
     * Actualiza nombre/html del formulario dentro de la solicitud (NO el base).
     */
    public function update(Request $request, $id)
    {
        $row = SolicitudeFormulario::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'html'   => 'nullable|string',
        ]);
//        user_id
        $data['user_id'] = $request->user()->id;

        $row->update($data);

        return response()->json($row);
    }

    /**
     * Quitar (soft delete) un formulario de la solicitud
     */
    public function remove($id)
    {
        $row = SolicitudeFormulario::findOrFail($id);
        $row->delete();

        return response()->json(['message' => 'Formulario quitado']);
    }

    /**
     * PDF de un formulario específico (1 página, 2 copias izquierda/derecha)
     */
    public function pdfOne($id)
    {
        $row = SolicitudeFormulario::with(['solicitud.paciente', 'solicitud.doctor'])->findOrFail($id);
        $solicitud = $row->solicitud;

        $pdf = Pdf::loadView('pdf.inmunologia_formulario', [
            'solicitud' => $solicitud,
            'row'       => $row,
        ])->setPaper('legal', 'landscape');

        return $pdf->stream('INMUNOLOGIA_'.$solicitud->nro_registro.'_F'.$row->id.'.pdf');
    }

    /**
     * PDF de TODOS los formularios seleccionados del área (varias páginas)
     */
    public function pdfAll(Request $request, $solicitudeId)
    {
        $areaId = (int) $request->get('area_id', 0);
        if ($areaId <= 0) abort(422, 'area_id es requerido');

        $solicitud = Solicitude::with(['paciente', 'doctor'])->findOrFail($solicitudeId);

        $items = SolicitudeFormulario::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaId)
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('pdf.inmunologia_all', [
            'solicitud' => $solicitud,
            'items'     => $items,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('INMUNOLOGIA_'.$solicitud->nro_registro.'_TODO.pdf');
    }
}
