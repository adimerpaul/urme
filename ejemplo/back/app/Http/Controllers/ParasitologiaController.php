<?php

namespace App\Http\Controllers;

use App\Models\Parasitologia;
use App\Models\ServicioSolicitude;
use App\Models\Solicitude;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ParasitologiaController extends Controller
{
    public function showBySolicitude($solicitudeId)
    {
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
        ])->findOrFail($solicitudeId);

        $parasitologia = Parasitologia::firstOrNew([
            'solicitude_id' => $solicitudeId,
        ]);

        return response()->json([
            'solicitud'     => $solicitud,
            'parasitologia' => $parasitologia,
        ]);
    }

    public function upsert(Request $request, $solicitudeId)
    {
        $data = $request->all();
        $data['solicitude_id'] = $solicitudeId;

        // si es SIMPLE, limpiamos seriado
        if (($data['tipo'] ?? 'SIMPLE') === 'SIMPLE') {
            $data['descripcion_muestra_1'] = null;
            $data['descripcion_muestra_2'] = null;
            $data['descripcion_muestra_3'] = null;
        } else { // SERIADO
            $data['descripcion_muestra'] = null;
        }

        $parasitologia = Parasitologia::updateOrCreate(
            [
                'solicitude_id' => $solicitudeId,
            ],
            array_merge($data, ['user_id' => $request->user()->id])
        );
        $areaIdHemato = 4;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdHemato)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);
        $soliditude = Solicitude::find($solicitudeId);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();
        $soliditude->save();

        return response()->json($parasitologia);
    }

    public function destroyBySolicitude($solicitudeId)
    {
        $parasitologia = Parasitologia::where('solicitude_id', $solicitudeId)->firstOrFail();
        $parasitologia->delete();

        return response()->json(['message' => 'Parasitología eliminada']);
    }

    public function pdfBySolicitude($code)
    {
        $solicitudeId = Parasitologia::where('code', $code)
            ->value('solicitude_id');
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
        ])->findOrFail($solicitudeId);

        $parasitologia = Parasitologia::where('solicitude_id', $solicitudeId)->first();

        $url = url("/api/parasitologia/solicitud/{$code}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );

        $pdf = Pdf::loadView('pdf.parasitologia', [
            'solicitud'     => $solicitud,
            'parasitologia' => $parasitologia,
            'qrSvgBase64'   => $qrSvgBase64,
            'url'           => $url,
        ])->setPaper('legal');

        return $pdf->stream('PARASITOLOGIA_'.$solicitud->nro_registro.'.pdf');
    }
}
