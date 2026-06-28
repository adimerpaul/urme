<?php

namespace App\Http\Controllers;

use App\Models\QuimicaSanguinea;
use App\Models\ServicioSolicitude;
use App\Models\Solicitude;
use App\Models\Area;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QuimicaSanguineaController extends Controller
{
    function pdfCitoQuimicoBySolicitude($code)
    {
        $solicitudeId = QuimicaSanguinea::where('code', $code)->value('solicitude_id');

        $solicitud = Solicitude::with(['paciente', 'doctor', 'servicios.area'])->findOrFail($solicitudeId);
        $quimica   = QuimicaSanguinea::where('solicitude_id', $solicitudeId)->first();

        if (!$quimica) {
            $quimica = new QuimicaSanguinea();
            $quimica->solicitude_id = $solicitudeId;
        }
        $url = url("/api/quimica-sanguinea/solicitud/{$code}/pdf/citoquimico");
        $qrSvgBase64 = base64_encode(QrCode::format('svg')->size(110)->margin(1)->generate($url));
        $pdf = Pdf::loadView('pdf.quimica_sanguinea_citoquimico', [
            'solicitud'     => $solicitud,
            'quimica'       => $quimica,
            'qrSvgBase64'   => $qrSvgBase64,
            'url'           => $url,
        ])->setPaper('letter');
        return $pdf->stream('CITOQUIMICO_'.$solicitud->nro_registro.'.pdf');
    }
    function pdfToleranciaBySolicitude($code)
    {
        $solicitudeId = QuimicaSanguinea::where('code', $code)->value('solicitude_id');

        $solicitud = Solicitude::with(['paciente', 'doctor', 'servicios.area'])->findOrFail($solicitudeId);
        $quimica   = QuimicaSanguinea::where('solicitude_id', $solicitudeId)->first();

        if (!$quimica) {
            $quimica = new QuimicaSanguinea();
            $quimica->solicitude_id = $solicitudeId;
        }

        // ✅ datos dinámicos 1..5 (solo no-null)
        $series = $this->buildToleranciaSeries($quimica);

        // ✅ generar chart base64
        $chartBase64 = $this->renderToleranciaChartBase64($series);

        $url = url("/api/quimica-sanguinea/solicitud/{$code}/pdf/tolerancia");
        $qrSvgBase64 = base64_encode(QrCode::format('svg')->size(110)->margin(1)->generate($url));

        $pdf = Pdf::loadView('pdf.quimica_sanguinea_tolerancia', [
            'solicitud'     => $solicitud,
            'quimica'       => $quimica,
            'qrSvgBase64'   => $qrSvgBase64,
            'url'           => $url,
            'series'        => $series,
            'chartBase64'   => $chartBase64,
        ])->setPaper('letter');

        return $pdf->stream('TOLERANCIA_'.$solicitud->nro_registro.'.pdf');
    }
    private function buildToleranciaSeries($quimica): array
    {
        $series = [];

        for ($i=1; $i<=5; $i++) {
            $v = $quimica->{"tolerancia_glucosa_{$i}h"} ?? null;
            $h = $quimica->{"tolerancia_hora_{$i}h"} ?? null;

            // Si está vacío, se salta (no grafica)
            if ($v === null || $v === '') continue;

            $series[] = [
                'toma'  => $i,                 // 1..5
                'valor' => (float) $v,          // mg/dl
                'hora'  => $h ? substr($h, 0, 5) : null,  // "HH:MM"
            ];
        }

        return $series;
    }
    private function renderToleranciaChartBase64(array $series): ?string
    {
        // Si no hay datos, no hay gráfica
        if (count($series) === 0) return null;

        // Tamaño de imagen (ajusta a tu gusto)
        $w = 820;
        $h = 280;

        $img = imagecreatetruecolor($w, $h);

        // Colores
        $white = imagecolorallocate($img, 255,255,255);
        $black = imagecolorallocate($img, 0,0,0);
        $gray  = imagecolorallocate($img, 120,120,120);
        $blue  = imagecolorallocate($img, 40,90,200);

        imagefill($img, 0, 0, $white);

        // Márgenes
        $ml = 55;   // left
        $mr = 20;   // right
        $mt = 20;   // top
        $mb = 55;   // bottom

        // Área de plot
        $pw = $w - $ml - $mr;
        $ph = $h - $mt - $mb;

        // Min/Max valores
        $vals = array_map(fn($p)=>$p['valor'], $series);
        $minV = min($vals);
        $maxV = max($vals);

        // Para que no se pegue al borde
        $pad = max(10, ($maxV - $minV) * 0.15);
        $minV -= $pad;
        $maxV += $pad;

        // Ejes
        imageline($img, $ml, $mt, $ml, $mt + $ph, $black);
        imageline($img, $ml, $mt + $ph, $ml + $pw, $mt + $ph, $black);

        // Grilla horizontal (4 líneas)
        $gridLines = 4;
        for ($g=1; $g<=$gridLines; $g++) {
            $y = (int)($mt + $ph - ($ph * $g / ($gridLines+1)));
            imageline($img, $ml, $y, $ml + $pw, $y, $gray);

            $valTick = $minV + (($maxV - $minV) * $g / ($gridLines+1));
            imagestring($img, 2, 5, $y-7, number_format($valTick, 0), $gray);
        }

        // X: posiciones equidistantes según cantidad de puntos
        $n = count($series);
        $stepX = ($n > 1) ? ($pw / ($n - 1)) : 0;

        $points = [];
        foreach ($series as $idx => $p) {
            $x = (int)($ml + ($idx * $stepX));
            // map Y (valor alto arriba)
            $y = (int)($mt + $ph - (($p['valor'] - $minV) / ($maxV - $minV) * $ph));
            $points[] = [$x, $y, $p];
        }

        // Línea
        for ($i=0; $i<count($points)-1; $i++) {
            imageline($img, $points[$i][0], $points[$i][1], $points[$i+1][0], $points[$i+1][1], $blue);
        }

        // Puntos + labels + eje X (1..n)
        foreach ($points as $i => [$x,$y,$p]) {
            imagefilledellipse($img, $x, $y, 9, 9, $blue);

            // Valor encima del punto
            imagestring($img, 3, $x - 15, $y - 22, number_format($p['valor'], 1), $black);

            // Número de toma en X
            $xLabel = (string)$p['toma'];
            imagestring($img, 3, $x - 3, $mt + $ph + 12, $xLabel, $black);
        }

        // Export PNG -> base64
        ob_start();
        imagepng($img);
        $raw = ob_get_clean();
        imagedestroy($img);

        return base64_encode($raw);
    }

    public function pdfBySolicitude($code)
    {
        $solicitudeId = QuimicaSanguinea::where('code', $code)
            ->value('solicitude_id');
        $solicitud = Solicitude::with(['paciente', 'doctor', 'servicios.area'])
            ->findOrFail($solicitudeId);

        $quimica = QuimicaSanguinea::where('solicitude_id', $solicitudeId)->first();

        if (!$quimica) {
            $quimica = new QuimicaSanguinea();
            $quimica->solicitude_id = $solicitudeId;
        }

        $areaQuimica = Area::where('title', 'QUÍMICA SANGUÍNEA Y SEROLOGÍA')
            ->orWhere('title', 'Química Sanguínea y Serología')
            ->first();

        $rangos = [];
        if ($areaQuimica) {
            $rangos = $areaQuimica->rangos()->orderBy('id')->get();
        }
        $url = url("/api/quimica-sanguinea/solicitud/{$code}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );

        $pdf = Pdf::loadView('pdf.quimica_sanguinea', [
            'solicitud' => $solicitud,
            'quimica'   => $quimica,
            'rangos'    => $rangos,
            'qrSvgBase64' => $qrSvgBase64,
            'url' => $url,
//            landscape
        ])->setPaper('letter');

        return $pdf->stream('QUIMICA_'.$solicitud->nro_registro.'.pdf');
    }

    /**
     * Devuelve datos de cabecera de la solicitud + química sanguínea (si existe)
     * + rangos del área Química Sanguínea y Serología.
     */
    public function showBySolicitude($solicitudeId)
    {
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
        ])->findOrFail($solicitudeId);
//        error_log('Solicitud ID: ' . $solicitudeId);

        $quimica = QuimicaSanguinea::firstOrNew([
            'solicitude_id' => $solicitudeId,
        ]);

        // Buscar área de Química Sanguínea
        $areaQuimica = Area::where('title', 'QUÍMICA SANGUÍNEA Y SEROLOGÍA')
            ->orWhere('title', 'Química Sanguínea y Serología')
            ->first();

        $rangos = [];
        if ($areaQuimica) {
            $rangos = $areaQuimica->rangos()
                ->orderBy('id')
                ->get();
        }

        return response()->json([
            'solicitud' => $solicitud,
            'quimica'   => $quimica,
            'rangos'    => $rangos,
        ]);
    }

    /**
     * UPSERT: crea o actualiza la química sanguínea de una solicitud.
     */
    public function upsert(Request $request, $solicitudeId)
    {
        $data = $request->all();
        $data['solicitude_id'] = $solicitudeId;

        $quimica = QuimicaSanguinea::updateOrCreate(
            [
                'solicitude_id' => $solicitudeId
            ],
//            $data'user_id' => $request->user()->id
            array_merge($data, ['user_id' => $request->user()->id])
        );
        $soliditude = Solicitude::find($solicitudeId);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();

        $areaIdQuimica = 2;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdQuimica)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);

        if ($request->muestra_rechazada === 'Si') {
            $soliditude->muestra_rechazada = 'Si';
            $soliditude->estado = 'MUESTRA RECHAZADA';
            $soliditude->muestra_observacion = $request->muestra_observacion;

            $solicitudRechazada = new \App\Models\SolicitudRechazada();
            $solicitudRechazada->solicitude_id = $solicitudeId;
            $solicitudRechazada->motivo = $request->muestra_observacion;
            $solicitudRechazada->fecha_hora = now();
            $solicitudRechazada->area_id = $request->user()->area_id;
            $solicitudRechazada->user_id = $request->user()->id;
            $solicitudRechazada->save();
        }

        $soliditude->save();

        return response()->json($quimica);
    }

    /**
     * Eliminar registro de química sanguínea de una solicitud.
     */
    public function destroyBySolicitude($solicitudeId)
    {
        $quimica = QuimicaSanguinea::where('solicitude_id', $solicitudeId)->firstOrFail();
        $quimica->delete();

        return response()->json(['message' => 'Química sanguínea eliminada']);
    }
}
