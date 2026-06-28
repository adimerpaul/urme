<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Solicitude;
use App\Models\ServicioSolicitude;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InmunologiaAnaliticaController extends Controller
{
    private const AREA_ID = 6;

    /**
     * GET /inmunologia-analitica/solicitud/{id}
     * Devuelve la solicitud con sus prestaciones de inmunología y los rangos vinculados.
     */
    public function show($solicitudId)
    {
        $solicitud = Solicitude::findOrFail($solicitudId);

        // Prestaciones de inmunología seleccionadas en esta solicitud (con realizado)
        $serviciosSolicitud = DB::table('servicio_solicitudes')
            ->where('solicitude_id', $solicitudId)
            ->where('area_id', self::AREA_ID)
            ->get()
            ->keyBy('servicio_id');

        $servicioIds = $serviciosSolicitud->keys();

        $prestaciones = Servicio::with([
            'rangos' => fn ($q) => $q->orderBy('servicio_rangos.orden')->orderBy('area_rangos.id'),
            'formulas',
        ])
            ->whereIn('id', $servicioIds)
            ->orderBy('codigo')
            ->get();

        // Resultados ya guardados para esta solicitud en área 6
        $resultados = DB::table('resultado_laboratorios')
            ->where('solicitude_id', $solicitudId)
            ->where('area_id', self::AREA_ID)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('area_rango_id');

        $prestacionesConResultados = $prestaciones->map(function ($servicio) use ($resultados, $serviciosSolicitud) {
            $ss = $serviciosSolicitud->get($servicio->id);
            $rangos = $servicio->rangos->map(function ($rango) use ($resultados) {
                $resultado = $resultados->get($rango->id);
                return [
                    'id'                   => $rango->id,
                    'rango_nombre'         => $rango->rango_nombre,
                    'unidad'               => $rango->unidad,
                    'interpretacion'       => $rango->interpretacion,
                    'rango_descripcion'    => $rango->rango_descripcion,
                    'rango_minimo'         => $rango->rango_minimo,
                    'rango_maximo'         => $rango->rango_maximo,
                    'rango_2_descripcion'  => $rango->rango_2_descripcion,
                    'rango_2_minimo'       => $rango->rango_2_minimo,
                    'rango_2_maximo'       => $rango->rango_2_maximo,
                    'rango_3_descripcion'  => $rango->rango_3_descripcion,
                    'rango_3_minimo'       => $rango->rango_3_minimo,
                    'rango_3_maximo'       => $rango->rango_3_maximo,
                    'rango_4_descripcion'  => $rango->rango_4_descripcion,
                    'rango_4_minimo'       => $rango->rango_4_minimo,
                    'rango_4_maximo'       => $rango->rango_4_maximo,
                    'rango_5_descripcion'  => $rango->rango_5_descripcion,
                    'rango_5_minimo'       => $rango->rango_5_minimo,
                    'rango_5_maximo'       => $rango->rango_5_maximo,
                    'metodo'               => $rango->metodo,
                    'muestra'              => $rango->muestra,
                    'marca'                => $rango->marca,
                    'perfil'               => $rango->perfil,
                    'nombre_variable'      => $rango->pivot->nombre_variable,
                    'orden'                => $rango->pivot->orden,
                    'resultado'            => $resultado ? [
                        'id'          => $resultado->id,
                        'valor_final' => $resultado->valor_final,
                        'observacion' => $resultado->observacion,
                    ] : null,
                ];
            });

            return [
                'servicio_id'   => $servicio->id,
                'nombre'        => $servicio->nombre,
                'metodo'        => $servicio->metodo,
                'subarea'       => $servicio->subarea,
                'rangos'        => $rangos,
                'formulas'      => $servicio->formulas->map(fn ($f) => [
                    'nombre_variable' => $f->nombre_variable,
                    'formula'         => $f->formula,
                    'label'           => $f->label,
                ]),
                'realizado'     => $ss->realizado ?? 'PENDIENTE',
                'realizado_por' => $ss->realizado_por ?? null,
            ];
        });

        return response()->json([
            'solicitud'    => [
                'id'                             => $solicitud->id,
                'codigo'                         => $solicitud->codigo,
                'inmunologia_analitica_codigo'   => $solicitud->inmunologia_analitica_codigo,
                'paciente_nombre'                => $solicitud->paciente_nombre,
                'paciente_edad'                  => $solicitud->paciente_edad,
                'paciente_genero'                => $solicitud->paciente_genero,
                'doctor_nombre'                  => $solicitud->doctor_nombre,
                'fecha_solicitud'                => $solicitud->fecha_solicitud,
                'estado'                         => $solicitud->estado,
            ],
            'prestaciones' => $prestacionesConResultados,
        ]);
    }

    /**
     * POST /inmunologia-analitica/solicitud/{id}/resultados
     * Guarda o actualiza los valores ingresados para cada rango y marca como REALIZADO.
     */
    public function saveResultados(Request $request, $solicitudId)
    {
        $solicitud = Solicitude::findOrFail($solicitudId);

        $data = $request->validate([
            'resultados'                => 'required|array',
            'resultados.*.area_rango_id'=> 'required|integer|exists:area_rangos,id',
            'resultados.*.valor_final'  => 'nullable|string|max:255',
            'resultados.*.observacion'  => 'nullable|string',
        ]);

        $now = now();

        foreach ($data['resultados'] as $item) {
            DB::table('resultado_laboratorios')->updateOrInsert(
                [
                    'solicitude_id' => $solicitudId,
                    'area_rango_id' => $item['area_rango_id'],
                    'area_id'       => self::AREA_ID,
                ],
                [
                    'valor_final' => $item['valor_final'] ?? null,
                    'observacion' => $item['observacion'] ?? null,
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ]
            );
        }

        ServicioSolicitude::where('solicitude_id', $solicitudId)
            ->where('area_id', self::AREA_ID)
            ->update([
                'realizado'     => 'REALIZADO',
                'realizado_por' => auth()->user()->name ?? null,
            ]);

        // Generar código único de acceso al PDF si todavía no existe
        if (! $solicitud->inmunologia_analitica_codigo) {
            $solicitud->inmunologia_analitica_codigo = (string) Str::uuid();
            $solicitud->save();
        }

        return response()->json([
            'message' => 'Resultados guardados',
            'codigo'  => $solicitud->inmunologia_analitica_codigo,
        ]);
    }

    /**
     * GET /inmunologia-analitica/resultado/{codigo}/pdf
     * Genera el PDF de resultados de inmunología usando el código UUID público.
     */
    public function pdfBySolicitude($codigo)
    {
        $solicitud = Solicitude::where('inmunologia_analitica_codigo', $codigo)->firstOrFail();
        $solicitudId = $solicitud->id;

        $serviciosSolicitud = DB::table('servicio_solicitudes')
            ->where('solicitude_id', $solicitudId)
            ->where('area_id', self::AREA_ID)
            ->get()
            ->keyBy('servicio_id');

        $servicioIds = $serviciosSolicitud->keys();

        $prestaciones = Servicio::with([
            'rangos' => fn ($q) => $q->orderBy('servicio_rangos.orden')->orderBy('area_rangos.id'),
        ])
            ->whereIn('id', $servicioIds)
            ->orderBy('codigo')
            ->get();

        $resultados = DB::table('resultado_laboratorios')
            ->where('solicitude_id', $solicitudId)
            ->where('area_id', self::AREA_ID)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('area_rango_id');

        $prestacionesData = $prestaciones->map(function ($servicio) use ($resultados, $serviciosSolicitud) {
            $ss = $serviciosSolicitud->get($servicio->id);
            $rangos = $servicio->rangos->map(function ($rango) use ($resultados) {
                $resultado = $resultados->get($rango->id);
                return (object)[
                    'id'             => $rango->id,
                    'rango_nombre'   => $rango->rango_nombre,
                    'unidad'         => $rango->unidad,
                    'interpretacion' => $rango->interpretacion,
                    'rango_minimo'   => $rango->rango_minimo,
                    'rango_maximo'   => $rango->rango_maximo,
                    'metodo'         => $rango->metodo,
                    'valor_final'    => $resultado->valor_final ?? null,
                ];
            });

            return (object)[
                'nombre'        => $servicio->nombre,
                'metodo'        => $servicio->metodo,
                'subarea'       => $servicio->subarea,
                'rangos'        => $rangos,
                'realizado_por' => $ss->realizado_por ?? null,
            ];
        })->filter(fn($p) => $p->rangos->isNotEmpty())->values();

        $url = url("/api/inmunologia-analitica/resultado/{$codigo}/pdf");
        $qrSvgBase64 = base64_encode(
            QrCode::format('svg')->size(110)->margin(1)->generate($url)
        );

        $pdf = Pdf::loadView('pdf.inmunologia_analitica', [
            'solicitud'    => $solicitud,
            'prestaciones' => $prestacionesData,
            'qrSvgBase64'  => $qrSvgBase64,
        ])->setPaper('letter');

        return $pdf->stream('INMUNOLOGIA_' . $solicitud->codigo . '.pdf');
    }
}
