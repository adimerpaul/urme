<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter landscape; margin: 10px 12px; }
        * { box-sizing: border-box; }

        body{
            margin:0; padding:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color:#111;
            line-height: 1;
        }

        .sheet{ width:100%; overflow:hidden; }
        .half{ width:48%; float:left; overflow:hidden; padding:0; }

        .half-left{ transform: scale(1.02); transform-origin: top left; padding-right: 6px; }
        .half-right{ transform: scale(1.02); transform-origin: top left; padding-left: 6px; }

        .title { font-weight:700; font-size: 10.2px; text-align:center; }
        .subtitle { font-size: 8px; text-align:center; margin-top: 1px; }
        .muted { color:#555; }

        .hr { border-top: 1.8px solid #111; margin: 2px 0; }
        .box { border: 1px solid #111; padding: 3px 4px; }
        .small { font-size: 7.6px; }
        .center { text-align:center; }
        .right { text-align:right; }
        .bold { font-weight:700; }
        .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }

        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        th, td{ border:1px solid #111; padding: 1.8px 3px; vertical-align: middle; }
        th{ background:#f2f2f2; font-weight:700; font-size: 7.8px; }
        .no-border td, .no-border th{ border:none; padding:0; }

        .form-grid td{
            border:none;
            padding: 2px 3px 2px 0;
            vertical-align: bottom;
            font-size: 7.6px;
        }
        .label{ font-weight:700; }
        .line{
            border-bottom: 1px solid #111;
            height: 12px;
            padding: 0 3px;
            font-size: 7.7px;
        }

        .section{ margin-top: 6px; }
        .section h3{
            margin: 0 0 2px;
            font-size: 8.2px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        /* tabla antibióticos estilo “microbiólogo” (sin cuadros pesados) */
        .ab-table th, .ab-table td{ border:none; }
        .ab-table td{ padding: 2.4px 2px; font-size: 8px; border-bottom: 1px solid #e6e6e6; }
        .ab-name{ width:86%; }
        .ab-val{ width:14%; text-align:center; font-weight:700; }
        .S{ color:#0a7a0a; }
        .R{ color:#c10015; }
        .I{ color:#b26a00; }

        .clearfix::after{ content:""; display:block; clear:both; }
    </style>
</head>
<body>

@php
    $val = fn($v, $d='—') => ($v === null || $v === '') ? $d : $v;

    $cultivo = $cultivo ?? null;
    $ab = $cultivo?->antibiograma ?? [];
    if (!is_array($ab)) $ab = [];

    // limpia filas vacías
    $ab = array_values(array_filter($ab, function($x){
      return is_array($x) && !empty(trim($x['antibiotico'] ?? ''));
    }));

    // normaliza estado
    $normEstado = function($e){
      $e = strtoupper(trim((string)$e));
      return in_array($e, ['S','R','I']) ? $e : '';
    };

    // divide en 2 columnas como en el papel
    $mid = (int) ceil(count($ab) / 2);
    $abLeft  = array_slice($ab, 0, $mid);
    $abRight = array_slice($ab, $mid);

@endphp

<div class="sheet clearfix">
    @foreach(['left', 'right'] as $side)
        <div class="half half-{{ $side }}" style="margin: 10px 6px;">

            <!-- HEADER -->
            <table class="no-border">
                <tr>
                    <td style="width:15%;">
                        <img src="{{ public_path('img/logo-hospital.png') }}" style="width:58px;">
                    </td>
                    <td>
                        <div class="title">HOSPITAL GENERAL SAN JUAN DE DIOS ORURO BLOQUE CENTRAL</div>
                        <div class="subtitle muted">LABORATORIO DE ANÁLISIS CLÍNICO - MICROBIOLÓGICO</div>
                        <div class="subtitle muted small">Dirección: San Felipe entre 6 de Octubre y Tarija</div>
                        <div class="subtitle muted small">REGISTRO CONALAB: 001 &nbsp;&nbsp; REGISTRO CODELAB: 000004</div>
                    </td>
                    <td style="width:15%;" class="right">
                        <img src="{{ public_path('img/logo-labo.png') }}" style="width:58px;">
                    </td>
                </tr>
            </table>

            <div class="hr"></div>

            <!-- FORM (mismo estilo Hematología) -->
            <table class="form-grid" style="margin-top:2px;">
                <tr>
                    <td style="width:18%"><span class="label">CÓDIGO:</span></td>
                    <td style="width:32%"><div class="line clip">{{ $solicitud->codigo ?? $solicitud->id }}</div></td>
                    <td style="width:20%"><span class="label">NRO. REGISTRO:</span></td>
                    <td style="width:30%"><div class="line clip">{{ $solicitud->nro_registro ?? '-' }}</div></td>
                </tr>

                <tr>
                    <td><span class="label">PACIENTE:</span></td>
                    <td><div class="line clip">{{ $solicitud->paciente_nombre ?? '-' }}</div></td>
                    <td><span class="label">EDAD:</span></td>
                    <td><div class="line clip">{{ $solicitud->paciente_edad ?? '-' }}</div></td>
                </tr>

                <tr>
                    <td><span class="label">MEDICO SOL.:</span></td>
                    <td><div class="line clip">{{ $solicitud->doctor_nombre ?? '-' }}</div></td>
                    <td><span class="label">SEXO:</span></td>
                    <td><div class="line clip">{{ $solicitud->paciente_genero ?? '-' }}</div></td>
                </tr>

                <tr>
                    <td><span class="label">FECHA SOL. MEDICO:</span></td>
                    <td><div class="line clip">{{ $solicitud->fecha_solicitud ?? '-' }}</div></td>
                    <td><span class="label">TIPO MUESTRA:</span></td>
                    <td><div class="line clip">{{ $solicitud->muestra_identificacion ?? '-' }}</div></td>
                </tr>

                <tr>
                    <td><span class="label">EST. DE SALUD:</span></td>
                    <td><div class="line clip">{{ $solicitud->establecimiento_salud ?? '-' }}</div></td>
                    <td><span class="label">CI:</span></td>
                    <td><div class="line clip">{{ $solicitud->paciente_ci ?? '-' }}</div></td>
                </tr>
            </table>

            <!-- TÍTULO -->
            <div class="center" style="margin-top:6px; font-weight:700; font-size:10px;">
                CULTIVO Y ANTIBIOGRAMA
            </div>

            <!-- DATOS “MICROBIOLOGÍA” (como hoja) -->
{{--            <div class="section">--}}
{{--                <table class="no-border">--}}
{{--                    <tr>--}}
{{--                        <td style="width:50%; padding-right:8px;">--}}
{{--                            <div class="small"><span class="bold">Número de identificación:</span> {{ $val($cultivo?->numero_identificacion, 'SUS') }}</div>--}}
{{--                            <div class="small"><span class="bold">Código microbiología:</span> {{ $val($cultivo?->codigo_microbiologia) }}</div>--}}
{{--                            <div class="small"><span class="bold">Institución:</span> {{ $val($cultivo?->institucion) }}</div>--}}
{{--                            <div class="small"><span class="bold">Fecha de ingreso:</span> {{ $val(optional($cultivo?->fecha_ingreso)->format('d-M-Y')) }}</div>--}}
{{--                        </td>--}}

{{--                        <td style="width:50%; padding-left:8px;">--}}
{{--                            <div class="small"><span class="bold">Reg. CONALAB N°:</span> 0004</div>--}}
{{--                            <div class="small"><span class="bold">Cultivo solicitado:</span> {{ $val($cultivo?->cultivo_solicitado) }}</div>--}}
{{--                            <div class="small"><span class="bold">Localización:</span> {{ $val($cultivo?->localizacion) }}</div>--}}
{{--                            <div class="small"><span class="bold">Servicio:</span> {{ $val($cultivo?->servicio) }}</div>--}}
{{--                            <div class="small"><span class="bold">Sala:</span> {{ $val($cultivo?->sala) }} &nbsp; <span class="bold">Cama:</span> {{ $val($cultivo?->cama) }}</div>--}}
{{--                            <div class="small"><span class="bold">Fecha de salida:</span> {{ $val(optional($cultivo?->fecha_salida)->format('d-M-Y')) }}</div>--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                </table>--}}
{{--            </div>--}}

            <!-- RESULTADOS -->
            <div class="section">
                <div class="small"><span class="bold">TINCIÓN GRAM (100x):</span> {{ $val($cultivo?->tincion_gram) }}</div>
                <div class="small" style="margin-top:3px;"><span class="bold">MICROORGANISMO IDENTIFICADO:</span></div>
                <div class="small" style="margin-top:2px;">
                    <span class="bold">Muestra / conteo de colonia:</span> {{ $val($cultivo?->conteo_colonia) }}
                </div>
                <div class="small" style="margin-top:2px;">
                    <span class="bold">Microorganismo:</span> {{ $val($cultivo?->microorganismo) }}
                </div>
            </div>

            <!-- ANTIBIOGRAMA en 2 columnas -->
            <div class="section">
                <table class="no-border">
                    <tr>
                        <td style="width:50%; padding-right:10px; vertical-align:top;">
                            <table class="ab-table">
                                @foreach($abLeft as $row)
                                    @php $e = $normEstado($row['estado'] ?? ''); @endphp
                                    <tr>
                                        <td class="ab-name">{{ $row['antibiotico'] }}</td>
                                        <td class="ab-val {{ $e }}">{{ $e ?: '' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>

                        <td style="width:50%; padding-left:10px; vertical-align:top;">
                            <table class="ab-table">
                                @foreach($abRight as $row)
                                    @php $e = $normEstado($row['estado'] ?? ''); @endphp
                                    <tr>
                                        <td class="ab-name">{{ $row['antibiotico'] }}</td>
                                        <td class="ab-val {{ $e }}">{{ $e ?: '' }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- MECANISMO / OBS -->
            @if(!empty($cultivo?->mecanismo_resistencia))
                <div class="section">
                    <div class="small"><span class="bold">MECANISMO DE RESISTENCIA:</span> {{ $cultivo->mecanismo_resistencia }}</div>
                </div>
            @endif

            <div class="section">
                <span class="bold small">OBSERVACIONES:</span>
                <div class="box" style="min-height:22px;">{{ $cultivo->observaciones ?? '' }}</div>
            </div>

            <!-- FIRMAS -->
            <table class="no-border" style="margin-top:6px;">
                <tr>
                    <td class="center" style="width:50%;">
                        ___________________________<br>
                        <span class="muted small">Firma / Sello</span>
                    </td>
                    <td class="center" style="width:50%;">
                        ___________________________<br>
                        <span class="muted small">Microbiólogo(a) / Responsable</span>
                    </td>
                </tr>
            </table>

        </div>
    @endforeach
</div>

</body>
</html>
