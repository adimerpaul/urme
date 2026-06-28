<?php

namespace App\Http\Controllers;

use App\Models\PanelRespiratorio;
use App\Models\ServicioSolicitude;
use App\Models\Solicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PanelRespiratorioController extends Controller
{
    public function showBySolicitude($id)
    {
        $solicitud = Solicitude::with(['paciente', 'doctor','servicios'])->findOrFail($id);

        $panel = PanelRespiratorio::firstOrNew([
            'solicitude_id' => $id
        ]);

        // defaults si está vacío
        $defaults = [
            'vrs_ab' => 'NO DETECTADO',
            'influenza_b' => 'NO DETECTADO',
            'influenza_a' => 'NO DETECTADO',
            'sars_cov_2' => 'NO DETECTADO',
            'streptococcus_pyogenes' => 'NO DETECTADO',
            'adenovirus' => 'NO DETECTADO',
            'rhinovirus' => 'NO DETECTADO',
            'coronavirus_229e_oc43' => 'NO DETECTADO',
            'parainfluenza_1_2' => 'NO DETECTADO',
            'coronavirus_nl63_hku1' => 'NO DETECTADO',
            'parainfluenza_3_4' => 'NO DETECTADO',
            'haemophilus_influenzae' => 'NO DETECTADO',
            'bordetella_pertussis' => 'NO DETECTADO',
            'streptococcus_pneumoniae' => 'NO DETECTADO',
            'bocavirus' => 'NO DETECTADO',
            'mycoplasma_pneumoniae' => 'NO DETECTADO',
            'metapneumovirus' => 'NO DETECTADO',
            'enterovirus' => 'NO DETECTADO',
            'legionella_pneumophila' => 'NO DETECTADO',
        ];

        foreach ($defaults as $k => $v) {
            if (empty($panel->{$k})) $panel->{$k} = $v;
        }

        return response()->json([
            'solicitud' => $solicitud,
            'panel'     => $panel,
        ]);
    }

    public function upsert(Request $request, $id)
    {
        $data = $request->all();
        $data['solicitude_id'] = $id;
        $existente = PanelRespiratorio::where('solicitude_id', $id)->first();
//        if (!$existente) {
//            $data['numeracion'] = (new PanelRespiratorio())->generateNumeracion();
//            error_log('Generando numeracion: '.$data['numeracion']);
//        }
        $data['numeracion'] = $request->input('numeracion', (new PanelRespiratorio())->generateNumeracion());

        $registro = PanelRespiratorio::updateOrCreate(
            [
                'solicitude_id' => $id,
            ],
            array_merge($data, ['user_id' => $request->user()->id])
        );
        $soliditude = Solicitude::find($id);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();
        $soliditude->save();
        $solicitudeId = $id;
        $areaIdHemato = 7;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdHemato)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);

        return response()->json($registro);
    }

    public function destroyBySolicitude($id)
    {
        $registro = PanelRespiratorio::where('solicitude_id', $id)->firstOrFail();
        $registro->delete();

        return response()->json(['message' => 'Registro eliminado']);
    }

    public function pdfBySolicitude($code)
    {
        $id = PanelRespiratorio::where('code', $code)->value('solicitude_id');
        $solicitud = Solicitude::with(['paciente', 'doctor'])->findOrFail($id);
        $panel = PanelRespiratorio::where('solicitude_id', $id)->first();

        $url = url("/api/panel-respiratorio/solicitud/{$code}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );

        $pdf = Pdf::loadView('pdf.panel_respiratorio', [
            'solicitud' => $solicitud,
            'panel' => $panel,
            'qrSvgBase64' => $qrSvgBase64,
            'url' => $url,
        ])->setPaper('letter', 'landscape');


        return $pdf->stream('PANEL_RESP_'.$solicitud->nro_registro.'.pdf');
    }
}
