<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <style>
        @page { size: letter; margin: 10px 12px; }
        * { box-sizing: border-box; }

        body{
            margin:0; padding:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color:#111;
            line-height: 1.15;
        }

        /* 2 columnas */
        .sheet{ width:100%; overflow:hidden; }
        .half{ width:48%; float:left; overflow:hidden; padding:0; }

        /* Ajuste fino */
        .half-left{
            transform: scale(1.02);
            transform-origin: top left;
            padding-right: 6px;
        }
        .half-right{
            transform: scale(1.02);
            transform-origin: top left;
            padding-left: 6px;
        }

        /* Tipos */
        .title { font-weight:700; font-size: 12px; text-align:center; }
        .subtitle { font-size: 12px; text-align:center; margin-top: 1px; }
        .muted { color:#555; }
        .small { font-size: 12px; }
        .center { text-align:center; }
        .right { text-align:right; }
        .bold { font-weight:700; }

        /* Separadores / cajas */
        .hr { border-top: 1.8px solid #111; margin: 2px 0; }
        .box { border: 1px solid #111; padding: 3px 4px; }

        /* Tablas */
        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        th, td{ border:1px solid #111; padding: 2px 3px; vertical-align: middle; }
        th{ background:#f2f2f2; font-weight:700; font-size: 12px; }
        .no-border td, .no-border th{ border:none; padding:0; }
        .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* Form grid (cabecera datos) */
        .form-grid td{
            border:none;
            padding: 2px 3px 2px 0;
            vertical-align: bottom;
            font-size: 12px;
        }
        .label{ font-weight:700; }
        .line{
            border-bottom: 1px solid #111;
            height: 12px;
            padding: 0 3px;
            font-size: 12px;
        }

        /* Secciones */
        .section{ margin-top: 4px; }
        .section h3{
            margin: 0 0 2px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        /* Resultado */
        .res-ok{ font-weight:700; }
        .res-pos{ font-weight:700; color:#c10015; } /* DETECTADO */
        .note{
            border: 1px dashed #444;
            padding: 3px 4px;
            font-size: 12px;
            color:#333;
        }

        /* Footer */
        .footer{
            margin-top: 6px;
            font-size: 12px;
            color:#444;
        }

        /* Limpieza float */
        .clearfix::after{
            content:"";
            display:block;
            clear:both;
        }
    </style>
</head>

<body>

@php
    // Helpers simples (evitar null)
    $val = fn($v, $d='—') => ($v === null || $v === '') ? $d : $v;

    $norm = function($v){
        $v = strtoupper(trim((string)$v));
        if ($v === 'DETECTADO') return 'DETECTADO';
        if ($v === 'NO DETECTADO' || $v === 'NO_DETECTADO' || $v === 'NEGATIVO') return 'NO DETECTADO';
        return $v ?: '—';
    };

    $classRes = function($v) use ($norm){
        return $norm($v) === 'DETECTADO' ? 'res-pos' : 'res-ok';
    };

    // Defaults estilo reporte
    $tipoMuestra = $solicitud->muestra_identificacion ?? 'HISOPADO CERVICAL';
    $metodo = 'PCR EN TIEMPO REAL';
    $areaTitulo = 'BIOLOGÍA MOLECULAR';
@endphp
{{--sheet clearfix--}}
<div class="">

    @foreach(['left'] as $side)
{{--        half half-{{ $side }}--}}
        <div class="" style="margin: 20px 20px;">

            {!! view('components.headerSinCabecera', ['solicitud' => $solicitud])->render() !!}
{{--            <div class="section " style="margin-top:4px;">--}}
{{--                <b>Fecha de muestra:</b>--}}
{{--                {{ $solicitud->fecha_envio_analitica ? \Carbon\Carbon::parse($solicitud->fecha_envio_analitica)->format('d/m/Y') : '-' }}--}}
{{--                <b>Tipo de muestra</b>--}}
{{--                @for($i = 0; $i < count($solicitud->preAnaliticaMuestras); $i++)--}}
{{--                    @if($solicitud->preAnaliticaMuestras[$i]->selected)--}}
{{--                        {{ $solicitud->preAnaliticaMuestras[$i]->nombre }},--}}
{{--                    @endif--}}
{{--                @endfor--}}
{{--                <b>Codigo Interno</b>--}}
{{--                {{ $papiloma->numeracion ?? '-' }}--}}
{{--            </div>--}}
            <table class="no-border section" style="margin-top:4px;">
                <tr>
                    <td>
                        <b>Fecha de muestra:</b>
                        {{ $solicitud->fecha_envio_analitica ? \Carbon\Carbon::parse($solicitud->fecha_envio_analitica)->format('d/m/Y') : '-' }}
                    </td>
                    <td>
                        <b>Tipo de muestra:</b>
                        @for($i = 0; $i < count($solicitud->preAnaliticaMuestras); $i++)
                            @if($solicitud->preAnaliticaMuestras[$i]->selected && $solicitud->preAnaliticaMuestras[$i]->areaTipoMuestra->area_id == 7)
                                {{ $solicitud->preAnaliticaMuestras[$i]->nombre }},
                            @endif
                        @endfor
                    </td>
                    <td style="text-align: right">
                        <b>Código Interno:</b>
                        {{ $papiloma->numeracion ?? '-' }}
                    </td>
                </tr>
            </table>

            <!-- ===== TÍTULO ESTUDIO ===== -->
            <div class="section center" style="margin-top:10px; font-weight:700; font-size:12px;">
                RESULTADOS DE VIRUS DE PAPILOMA HUMANO POR PCR
            </div>
            <div class="center muted small" style="margin-top:5px;">
                Método: Reacción en Cadena de la Polimerasa en Tiempo Real
                Equipo: {{ $papiloma->equipo ?? '-' }}
            </div>

            <!-- ===== TABLA RESULTADOS ===== -->
            <div class="section" style="margin-top:4px;">
                <table>
                    <thead>
                    <tr>
                        <th style="width:52%;">DETERMINACIÓN</th>
                        <th style="width:24%;" class="center">RESULTADO</th>
                        <th style="width:24%;" class="center">VALORES DE REFERENCIA</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Determinación de HPV de alto riesgo*</td>
                        <td class="center {{ $classRes($papiloma->hpv_alto_riesgo ?? null) }}">{{ $norm($papiloma->hpv_alto_riesgo ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @if($papiloma->hpv_16 !== null)
                    <tr>
                        <td>Determinación de HPV 16</td>
                        <td class="center {{ $classRes($papiloma->hpv_16 ?? null) }}">{{ $norm($papiloma->hpv_16 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_18 !== null)
                    <tr>
                        <td>Determinación de HPV 18</td>
                        <td class="center {{ $classRes($papiloma->hpv_18 ?? null) }}">{{ $norm($papiloma->hpv_18 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_26 !== null)
                    <tr>
                        <td>Determinación de HPV 26</td>
                        <td class="center {{ $classRes($papiloma->hpv_26 ?? null) }}">{{ $norm($papiloma->hpv_26 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_31 !== null)
                    <tr>
                        <td>Determinación de HPV 31</td>
                        <td class="center {{ $classRes($papiloma->hpv_31 ?? null) }}">{{ $norm($papiloma->hpv_31 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_33 !== null)
                    <tr>
                        <td>Determinación de HPV 33</td>
                        <td class="center {{ $classRes($papiloma->hpv_33 ?? null) }}">{{ $norm($papiloma->hpv_33 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_35 !== null)
                    <tr>
                        <td>Determinación de HPV 35</td>
                        <td class="center {{ $classRes($papiloma->hpv_35 ?? null) }}">{{ $norm($papiloma->hpv_35 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_39 !== null)
                    <tr>
                        <td>Determinación de HPV 39</td>
                        <td class="center {{ $classRes($papiloma->hpv_39 ?? null) }}">{{ $norm($papiloma->hpv_39 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_45 !== null)
                        <tr>
                            <td>Determinación de HPV 45</td>
                            <td class="center {{ $classRes($papiloma->hpv_45 ?? null) }}">{{ $norm($papiloma->hpv_45 ?? 'NO DETECTADO') }}</td>
                            <td class="center">NO DETECTADO</td>
                        </tr>
                    @endif
                    @if($papiloma->hpv_51 !== null)
                    <tr>
                        <td>Determinación de HPV 51</td>
                        <td class="center {{ $classRes($papiloma->hpv_51 ?? null) }}">{{ $norm($papiloma->hpv_51 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_52 !== null)
                    <tr>
                        <td>Determinación de HPV 52</td>
                        <td class="center {{ $classRes($papiloma->hpv_52 ?? null) }}">{{ $norm($papiloma->hpv_52 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_53 !== null)
                    <tr>
                        <td>Determinación de HPV 53</td>
                        <td class="center {{ $classRes($papiloma->hpv_53 ?? null) }}">{{ $norm($papiloma->hpv_53 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_56 !== null)
                    <tr>
                        <td>Determinación de HPV 56</td>
                        <td class="center {{ $classRes($papiloma->hpv_56 ?? null) }}">{{ $norm($papiloma->hpv_56 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_58 !== null)
                    <tr>
                        <td>Determinación de HPV 58</td>
                        <td class="center {{ $classRes($papiloma->hpv_58 ?? null) }}">{{ $norm($papiloma->hpv_58 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_59 !== null)
                    <tr>
                        <td>Determinación de HPV 59</td>
                        <td class="center {{ $classRes($papiloma->hpv_59 ?? null) }}">{{ $norm($papiloma->hpv_59 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_66 !== null)
                    <tr>
                        <td>Determinación de HPV 66</td>
                        <td class="center {{ $classRes($papiloma->hpv_66 ?? null) }}">{{ $norm($papiloma->hpv_66 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_67 !== null)
                    <tr>
                        <td>Determinación de HPV 67</td>
                        <td class="center {{ $classRes($papiloma->hpv_67 ?? null) }}">{{ $norm($papiloma->hpv_67 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_68 !== null)
                    <tr>
                        <td>Determinación de HPV 68</td>
                        <td class="center {{ $classRes($papiloma->hpv_68 ?? null) }}">{{ $norm($papiloma->hpv_68 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_69 !== null)
                    <tr>
                        <td>Determinación de HPV 69</td>
                        <td class="center {{ $classRes($papiloma->hpv_69 ?? null) }}">{{ $norm($papiloma->hpv_69 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_70 !== null)
                    <tr>
                        <td>Determinación de HPV 70</td>
                        <td class="center {{ $classRes($papiloma->hpv_70 ?? null) }}">{{ $norm($papiloma->hpv_70 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_73 !== null)
                    <tr>
                        <td>Determinación de HPV 73</td>
                        <td class="center {{ $classRes($papiloma->hpv_73 ?? null) }}">{{ $norm($papiloma->hpv_73 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_82 !== null)
                    <tr>
                        <td>Determinación de HPV 82</td>
                        <td class="center {{ $classRes($papiloma->hpv_82 ?? null) }}">{{ $norm($papiloma->hpv_82 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    @if($papiloma->hpv_97 !== null)
                    <tr>
                        <td>Determinación de HPV 97</td>
                        <td class="center {{ $classRes($papiloma->hpv_97 ?? null) }}">{{ $norm($papiloma->hpv_97 ?? 'NO DETECTADO') }}</td>
                        <td class="center">NO DETECTADO</td>
                    </tr>
                    @endif
                    </tbody>
                </table>
            </div>

            <!-- ===== INTERPRETACIÓN ===== -->
            <div class="section">
                <div class="note">
                    <span class="bold">*Interpretación de resultados:</span>
                    <span class="bold">NO DETECTADO</span> (NEGATIVO) &nbsp;;&nbsp;
                    <span class="bold">DETECTADO</span> (POSITIVO)
                </div>
            </div>

            <!-- ===== MÉTODO / GENOTIPOS ===== -->
            <div class="section">
                <div class="small" style="margin-top:3px;">
                    <span class="bold">MÉTODO:</span>
                    Amplificación de ácidos nucleicos (ADN) por PCR en tiempo real.
                </div>
                <div class="small" style="margin-top:2px;">
                    Esta metodología permite la determinación específica de 24 tipos HPV de alto riesgo:
                    26, 30, 31, 33, 34, 35, 39, 51, 52, 53, 56, 58, 59, 66, 67, 68, 69, 70, 73, 82, 97
                    y permite la diferenciación del HPV 16, 18 y 45 en muestras obtenidas de cuello uterino.
                </div>
            </div>

            <!-- ===== OBSERVACIONES ===== -->
            <div class="section">
                <h3>Observaciones</h3>
                <div class="box" style="min-height:28px;">
                    {{ $papiloma->observaciones ?? '' }}
                </div>
            </div>

            <!-- ===== FIRMAS ===== -->
            <table class="no-border" style="margin-top:8px;">
                <tr>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">Firma / Sello</span>
                    </td>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">
                            {{$papiloma->user? $papiloma->user->name:'---'}} <br>
                            Bioquímico(a) / Responsable
                        </span>
                    </td>
                    <td class="center" style="width:34%;">
                        @if(!empty($qrSvgBase64))
                            <img
                                src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}"
                                style="width:80px; height:80px;"
                                alt="QR"
                            >
                        @endif
                    </td>
{{--                    <td class="center" style="width:34%;">--}}
{{--                        @if(!empty($qrSvgBase64))--}}
{{--                            <img--}}
{{--                                src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}"--}}
{{--                                style="width:80px; height:80px;"--}}
{{--                                alt="QR"--}}
{{--                            >--}}
{{--                        @endif--}}
{{--                    </td>--}}
                </tr>
            </table>

            <!-- ===== FOOTER ===== -->
            <div class="footer">
                EMITIDO POR: ORURO, RED URBANA, MUNICIPIO ORURO, LABORATORIO HOSP. GENERAL S.J.D.D. (ORURO)
            </div>

        </div>
    @endforeach

</div>

</body>
</html>
