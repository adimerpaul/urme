<?php

namespace App\Http\Controllers;

use App\Models\ServicioSolicitude;
use App\Models\Uroanalisis;
use App\Models\Solicitude;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class UroanalisisController extends Controller
{
    /**
     * Devuelve cabecera de solicitud + uroanálisis (si existe).
     */
    public function showBySolicitude($solicitudeId)
    {
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
        ])->findOrFail($solicitudeId);

        $uro = Uroanalisis::firstOrNew([
            'solicitude_id' => $solicitudeId,
        ]);

        return response()->json([
            'solicitud'   => $solicitud,
            'uroanalisis' => $uro,
        ]);
    }

    /**
     * Crea o actualiza el uroanálisis de una solicitud.
     */
    public function upsert(Request $request, $solicitudeId)
    {
        $data = $request->all();
//        error_log('Datos recibidos para uroanálisis: ' . json_encode($data));
        $data['solicitude_id'] = $solicitudeId;
//        valor_celulas
//        error_log('valor_celulas: ' . ($data['valor_celulas'] ?? 'no proporcionado'));


        $uro = Uroanalisis::updateOrCreate(
            [
                'solicitude_id' => $solicitudeId,
            ],
            array_merge($data, ['user_id' => $request->user()->id])
        );
        $areaIdHemato = 3;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdHemato)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);

        $soliditude = Solicitude::find($solicitudeId);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();
        $soliditude->save();

        return response()->json($uro);
    }

    /**
     * Elimina el uroanálisis de una solicitud.
     */
    public function destroyBySolicitude($solicitudeId)
    {
        $uro = Uroanalisis::where('solicitude_id', $solicitudeId)->firstOrFail();
        $uro->delete();

        return response()->json([
            'message' => 'Uroanálisis eliminado',
        ]);
    }
    public function pdfBySolicitude($code)
    {
        $solicitudeId = Uroanalisis::where('code', $code)
            ->value('solicitude_id');
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
        ])->findOrFail($solicitudeId);

        $uro = Uroanalisis::where('solicitude_id', $solicitudeId)->first();

        $url = url("/api/uroanalisis/solicitud/{$code}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );

        $pdf = Pdf::loadView('pdf.uroanalisis', [
            'solicitud'   => $solicitud,
            'uroanalisis' => $uro,
            'qrSvgBase64' => $qrSvgBase64,
            'url'         => $url,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('UROANALISIS_'.$solicitud->nro_registro.'.pdf');
    }

}
