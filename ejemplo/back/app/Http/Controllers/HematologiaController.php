<?php

namespace App\Http\Controllers;

use App\Models\Hematologia;
use App\Models\ResultadoLaboratorio;
use App\Models\ServicioSolicitude;
use App\Models\Solicitude;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class HematologiaController extends Controller
{
    /**
     * Devuelve datos de cabecera de la solicitud + hematología (si existe)
     * + rangos del área Hematología.
     */
    public function showBySolicitude($solicitudeId)
    {
        // cabecera con relaciones que necesites en el front
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
//            'servicios'
        ])->findOrFail($solicitudeId);

        $hematologia = Hematologia::firstOrNew([
            'solicitude_id' => $solicitudeId,
        ]);

        // Buscar área de hematología (ajusta según el nombre que tengas)
        $areaHemato = Area::where('title', 'HEMATOLOGÍA')
            ->orWhere('title', 'Hematología')
            ->first();
//        error_log('Área Hematología: ' . ($areaHemato ? $areaHemato->id : 'No encontrada'));

        $rangos = [];
        if ($areaHemato) {
            // usamos la relación ->rangos() que ya tienes en el modelo Area
            $rangos = $areaHemato->rangos()
                ->orderBy('id')
                ->get();
        }

        return response()->json([
            'solicitud'   => $solicitud,
            'hematologia' => $hematologia,
            'rangos'      => $rangos,
        ]);
    }

    /**
     * UPSERT: crea o actualiza la hematología de una solicitud.
     */
    public function upsert(Request $request, $solicitudeId)
    {
        $data = $request->all();
        $data['solicitude_id'] = $solicitudeId;
        $user = $request->user();

        $hematologia = Hematologia::updateOrCreate(
            [
                'solicitude_id' => $solicitudeId,
            ],
            array_merge($data, ['user_id' => $user->id])
        );

        $areaIdHemato = 1;
        ServicioSolicitude::where('solicitude_id', $solicitudeId)
            ->where('area_id', $areaIdHemato)
            ->update(['realizado' => 'REALIZADO', 'realizado_por' => auth()->user()->name ?? null]);


        $soliditude = Solicitude::find($solicitudeId);
        $soliditude->estado = 'ANALIZADO';
        $soliditude->fecha_finalizacion = now();
//        $soliditude->user_analitica_id = $request->user()->id;

//        error_log('Muestra rechazada: ' . $request->muestra_rechazada);
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

        $this->syncResultados($solicitudeId, $hematologia, $areaIdHemato);

        return response()->json($hematologia);
    }

    /**
     * Mapeo rango_nombre (normalizado) → columna en hematologias.
     * Clave: rango_nombre en minúsculas sin tildes.
     */
    private const COLUMNA_MAP = [
        'globulos rojos'                  => 'globulos_rojos',
        'globulos blancos (leucocitos)'   => 'globulos_blancos',
        'globulos blancos'                => 'globulos_blancos',
        'leucocitos totales'              => 'leucocitos_totales',
        'plaquetas'                       => 'plaquetas',
        'hemoglobina'                     => 'hemoglobina',
        'hematocrito'                     => 'hematocrito',
        'v.c.m.'                          => 'vcm',
        'vcm'                             => 'vcm',
        'hb.c.m.'                         => 'hbcm',
        'hbcm'                            => 'hbcm',
        'chcm'                            => 'chcm',
        'basofilos'                       => 'basofilos_porcentaje',
        'basilos (absoluto)'              => 'basofilos_absoluto',
        'basofilos (absoluto)'            => 'basofilos_absoluto',
        'eosinofilos'                     => 'eosinofilos_porcentaje',
        'eosinofilos (absoluto)'          => 'eosinofilos_absoluto',
        'cayados'                         => 'cayados_porcentaje',
        'cayados (absoluto)'              => 'cayados_absoluto',
        'segmentados'                     => 'segmentados_porcentaje',
        'segmentados (absoluto)'          => 'segmentados_absoluto',
        'linfocitos'                      => 'linfocitos_porcentaje',
        'linfocitos (absoluto)'           => 'linfocitos_absoluto',
        'monocitos'                       => 'monocitos_porcentaje',
        'monocitos (absoluto)'            => 'monocitos_absoluto',
        'blastos'                         => 'blastos_porcentaje',
        'metamielocito'                   => 'metamielocitos_porcentaje',
        'eritroblastos'                   => 'eritroblastos_porcentaje',
        'fibrinogeno'                     => 'fibrinogeno',
        'dimeros d'                       => 'dimeros_d',
        'reticulocitos'                   => 'ipr2',
        'ipr'                             => 'ipr',
        'rc'                              => 'rc',
        'tiempo protrombina'              => 'tiempo_protrombina',
        'actividad protrombina'           => 'actividad_protrombina',
        'actividad de protrombina'        => 'actividad_protrombina',
        'inr'                             => 'inr',
        'aptt'                            => 'aptt',
        'ves'                             => 'ves',
    ];

    private function normalizeRango(string $s): string
    {
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ü'=>'U','Ñ'=>'N',
        ];
        return trim(mb_strtolower(strtr($s, $map)));
    }

    private function syncResultados(int $solicitudeId, Hematologia $hemato, int $areaId): void
    {
        $rangos = DB::table('area_rangos')
            ->where('area_id', $areaId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('rango_minimo')->orWhereNotNull('rango_maximo');
            })
            ->get();

        foreach ($rangos as $rango) {
            $key    = $this->normalizeRango($rango->rango_nombre);
            $column = self::COLUMNA_MAP[$key] ?? null;
            if (!$column) {
                continue;
            }
            $valor = $hemato->$column;
            if ($valor === null || $valor === '') {
                continue;
            }

            ResultadoLaboratorio::withTrashed()->updateOrCreate(
                ['solicitude_id' => $solicitudeId, 'area_rango_id' => $rango->id],
                [
                    'area_id'     => $areaId,
                    'valor_final' => (float) $valor,
                    'unidad'      => $rango->unidad,
                    'deleted_at'  => null,
                ]
            );
        }
    }

    /**
     * (Opcional) eliminar registro de hematología de una solicitud.
     */
    public function destroyBySolicitude($solicitudeId)
    {
        $hematologia = Hematologia::where('solicitude_id', $solicitudeId)->firstOrFail();
        $hematologia->delete();

        return response()->json(['message' => 'Hematología eliminada']);
    }
    public function pdfBySolicitude($code)
    {
        // 1) buscar solicitud por code en hematología
        $solicitudeId = Hematologia::where('code', $code)->value('solicitude_id');
        if (!$solicitudeId) {
            abort(404, 'No se encontró la solicitud para ese código.');
        }

        // 2) solicitud con relaciones
        $solicitud = Solicitude::with([
            'paciente',
            'doctor',
            'servicios.area',
        ])->findOrFail($solicitudeId);

        // 3) hematología
        $hematologia = Hematologia::where('solicitude_id', $solicitudeId)->first();

        // Si quieres que nunca sea null:
        // $hematologia = Hematologia::firstOrNew(['solicitude_id' => $solicitudeId]);

        // 4) rangos del área Hematología
        $areaHemato = Area::where('title', 'HEMATOLOGÍA')
            ->orWhere('title', 'Hematología')
            ->first();

        $rangos = [];
        if ($areaHemato) {
            $rangos = $areaHemato->rangos()->orderBy('id')->get();
        }

        // 5) QR apuntando al mismo PDF
        $url = url("/api/hematologia/solicitud/{$code}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );
//        return $solicitud->preAnaliticaMuestras;

        // 6) generar PDF
        $pdf = Pdf::loadView('pdf.hematologia', [
            'solicitud'   => $solicitud,
            'hematologia' => $hematologia,
            'rangos'      => $rangos,
            'qrSvgBase64' => $qrSvgBase64,
            'qrUrl'       => $url,
        ])->setPaper('legal');

        $nro = $solicitud->nro_registro ?? $solicitud->id;
        return $pdf->stream('HEMATOLOGIA_'.$nro.'.pdf');
    }
}
