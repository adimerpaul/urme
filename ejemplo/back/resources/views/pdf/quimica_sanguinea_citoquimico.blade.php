<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }

        body{
            margin:0;
            padding:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.08;
            color:#111;
        }

        .muted{ color:#666; }
        .bold{ font-weight:700; }
        .center{ text-align:center; }
        .small{ font-size:6.6px; }

        .block{
            page-break-inside: avoid;
        }
        .block .title{
            padding:2px 3px;
            font-size:7.6px;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:.02em;
        }
        .block .body{ padding:2px 3px; }

        .tbl{
            width:100%;
            border-collapse:collapse;
            table-layout:fixed;
        }
        .tbl th, .tbl td{
            border:1px solid #111;
            padding:1.5px 2px;
            vertical-align:middle;
        }
        .tbl th{
            background:#f7f7f7;
            font-size: 12px;
        }
        .tbl td{ font-size: 12px; }
    </style>
</head>

<body>
@php
    $q = $quimica ?? null;

    $fmt = function($value, $suffix = '') {
        if ($value === null || $value === '') return '';
        return trim($value . ($suffix ? ' ' . $suffix : ''));
    };
@endphp

<table>
    <tr>
        <td style="width:50%; vertical-align:top; padding:0 4px;">
            <div style="margin-top:-30px;">
                {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud' => $q->created_at ?? null])->render() !!}
            </div>

            <div class="center bold" style="font-size:12px; margin:2px 0; margin-top: 5px">CITOQUÍMICO</div>
            <div class="center bold" style="font-size:11px; margin:2px 0;">
                MUESTRA: {{ $q->tipo_de_muestra ?: 'LÍQUIDO CEFALORRAQUÍDEO' }}
            </div>

            <div class="center small muted" style="margin-bottom:4px;">
                Método: {{ $q->metodo ?: '—' }} &nbsp; • &nbsp;
                Equipo: {{ $q->equipo == 'Otros' ? $q->equipo_otro : ($q->equipo ?? '—') }}
            </div>

            <div class="block">
                <div class="title">Examen físico</div>
                <div class="body">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th style="width:45%;">Parámetro</th>
                            <th style="width:55%;">Resultado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($q->citoquimico_cantidad !== null && $q->citoquimico_cantidad !== '')
                            <tr>
                                <td>Cantidad</td>
                                <td class="center">{{ $fmt($q->citoquimico_cantidad, 'ml') }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_color)
                            <tr>
                                <td>Color</td>
                                <td class="center">{{ $q->citoquimico_color }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_aspecto)
                            <tr>
                                <td>Aspecto</td>
                                <td class="center">{{ $q->citoquimico_aspecto }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="block">
                <div class="title">Examen químico</div>
                <div class="body">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th style="width:45%;">Parámetro</th>
                            <th style="width:55%;">Resultado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($q->citoquimico_glucosa !== null && $q->citoquimico_glucosa !== '')
                            <tr>
                                <td>Glucosa</td>
                                <td class="center">{{ $fmt($q->citoquimico_glucosa, 'mg/dL') }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_ph !== null && $q->citoquimico_ph !== '')
                            <tr>
                                <td>pH</td>
                                <td class="center">{{ $q->citoquimico_ph }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_proteinas_totales !== null && $q->citoquimico_proteinas_totales !== '')
                            <tr>
                                <td>Proteínas totales</td>
                                <td class="center">{{ $fmt($q->citoquimico_proteinas_totales, 'U/L') }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_densidad !== null && $q->citoquimico_densidad !== '')
                            <tr>
                                <td>Densidad</td>
                                <td class="center">{{ $q->citoquimico_densidad }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_albumina !== null && $q->citoquimico_albumina !== '')
                            <tr>
                                <td>Albúmina</td>
                                <td class="center">{{ $fmt($q->citoquimico_albumina, 'g/dL') }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_ldh !== null && $q->citoquimico_ldh !== '')
                            <tr>
                                <td>LDH</td>
                                <td class="center">{{ $fmt($q->citoquimico_ldh, 'U/L') }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="block">
                <div class="title">Examen microscópico</div>
                <div class="body">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th style="width:45%;">Parámetro</th>
                            <th style="width:55%;">Resultado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($q->citoquimico_globulos_blancos !== null && $q->citoquimico_globulos_blancos !== '')
                            <tr>
                                <td>Glóbulos blancos</td>
                                <td class="center">{{ $fmt($q->citoquimico_globulos_blancos, 'x mm3') }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="block">
                <div class="title">Recuento diferencial</div>
                <div class="body">
                    <table class="tbl">
                        <thead>
                        <tr>
                            <th style="width:45%;">Parámetro</th>
                            <th style="width:55%;">Resultado</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($q->citoquimico_polimorfonucleares !== null && $q->citoquimico_polimorfonucleares !== '')
                            <tr>
                                <td>Polimorfonucleares</td>
                                <td class="center">{{ $fmt($q->citoquimico_polimorfonucleares, '%') }}</td>
                            </tr>
                        @endif
                        @if($q->citoquimico_mononucleares !== null && $q->citoquimico_mononucleares !== '')
                            <tr>
                                <td>Mononucleares</td>
                                <td class="center">{{ $fmt($q->citoquimico_mononucleares, '%') }}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(!empty($q->observaciones))
                <div class="block">
                    <div class="title">Observaciones</div>
                    <div class="body">
                        <table class="tbl">
                            <tr>
                                <td>{{ $q->observaciones }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif
        </td>
    </tr>
</table>

</body>
</html>
