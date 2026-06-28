<?php

namespace App\Http\Controllers;

use App\Models\PapilomaHumano;
use App\Models\ServicioSolicitude;
use App\Models\Solicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PapilomaHumanoController extends Controller
{
    public function showBySolicitude($id)
    {
        $solicitud = Solicitude::with(['paciente', 'doctor','servicios.area'])
            ->findOrFail($id);

        $papiloma = PapilomaHumano::firstOrNew([
            'solicitude_id' => $id
        ]);

        return response()->json([
            'solicitud' => $solicitud,
            'papiloma'  => $papiloma,
        ]);
    }

    public function upsert(Request $request, $id)
    {
        $data = $request->all();
        $data['solicitude_id'] = $id;
//        $existente = PapilomaHumano::where('solicitude_id', $id)->first();
//        if (!$existente) {
//            $data['numeracion'] = (new PapilomaHumano())->generateNumeracion();
//        }
        $data['numeracion'] = $request->input('numeracion', (new PapilomaHumano())->generateNumeracion());
//        error_log('user id: ' . $request->user()->id);

        $registro = PapilomaHumano::updateOrCreate(
            [
                'solicitude_id' => $id,
            ],
            array_merge($data, ['user_id' => $request->user()->id])
        );
        $solicitudeId = $id;
        $areaIdHemato = 7;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdHemato)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);
        $soliditude = Solicitude::find($id);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();
        $soliditude->save();

        return response()->json($registro);
    }

    public function destroyBySolicitude($id)
    {
        $registro = PapilomaHumano::where('solicitude_id', $id)->firstOrFail();
        $registro->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }

    public function pdfBySolicitude($code)
    {
        $id = PapilomaHumano::where('code', $code)
            ->value('solicitude_id');
        $solicitud = Solicitude::with(['paciente', 'doctor','preAnaliticaMuestras.areaTipoMuestra'])->findOrFail($id);
        $papiloma = PapilomaHumano::where('solicitude_id', $id)->first();

        $url = url("/api/papiloma-humano/solicitud/{$code}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );
//        return $solicitud->preAnaliticaMuestras;

        $pdf = Pdf::loadView('pdf.papiloma_humano', [
            'solicitud' => $solicitud,
            'papiloma'  => $papiloma,
            'qrSvgBase64' => $qrSvgBase64,
            'url' => $url,
        ])->setPaper('letter');

        return $pdf->stream('VPH_'.$solicitud->nro_registro.'.pdf');
    }
}
