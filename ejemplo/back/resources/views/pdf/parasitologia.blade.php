<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: legal; margin: 10px 12px; }
        * { box-sizing: border-box; }
        body{ margin:0; padding:0; font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#111; line-height: 1; }

        .sheet{ width:100%; overflow:hidden; }
        .half{ width:48%; float:left; overflow:hidden; padding:0; }
        .half-left{ transform: scale(1.02); transform-origin: top left; padding-right: 6px; }
        .half-right{ transform: scale(1.02); transform-origin: top left; padding-left: 6px; }

        .title { font-weight:700; font-size: 12px; text-align:center; }
        .subtitle { font-size: 12px; text-align:center; margin-top: 1px; }
        .muted { color:#555; }
        .small { font-size: 12px; }

        .hr { border-top: 1.8px solid #111; margin: 2px 0; }
        .box { border: 1px solid #111; padding: 3px 4px; }
        .center { text-align:center; }
        .right { text-align:right; }

        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        th, td{ border:1px solid #111; padding: 1.8px 3px; vertical-align: middle; }
        th{ background:#f2f2f2; font-weight:700; font-size: 12px; }
        .no-border td, .no-border th{ border:none; padding:0; }

        .form-grid td{ border:none; padding: 2px 3px 2px 0; vertical-align: bottom; font-size: 12px; }
        .label{ font-weight:700; }
        .line{ border-bottom: 1px solid #111; height: 12px; padding: 0 3px; font-size: 12px; }

        .section{ margin-top: 4px; }
        .section h3{ margin: 0 0 2px; font-size: 12px; text-transform: uppercase; letter-spacing: .2px; }

        .clearfix::after{ content:""; display:block; clear:both; }
        img{ max-width: 100%; }
        .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }
    </style>
</head>

<body>
@php
    $p = $parasitologia;
    $tipo = $p->tipo ?? 'SIMPLE';
@endphp

<div >
    @foreach(['left'] as $side)
        <div  style="margin: 10px 6px;height: 49%">

            {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud'=>$p->created_at])->render() !!}

            <div class="section center" style="margin-top:20px; font-weight:700; font-size:12px;">
                PARASITOLOGÍA
            </div>

            <div class="center muted small" style="margin-top:1px;">
                Tipo: <b>{{ $tipo }}</b>
            </div>

            <!-- MACROSCOPÍA -->
            <div class="section">
                <h3>Macroscopía</h3>
                <table>
                    <thead>
                    <tr>
                        <th style="width:22%;">OLOR</th>
                        <th style="width:22%;">COLOR</th>
                        <th style="width:22%;">CONSISTENCIA</th>
                        <th style="width:34%;">BACTERIAS</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="center">{{ $p->olor ?? '' }}</td>
                        <td class="center">{{ $p->color ?? '' }}</td>
                        <td class="center">{{ $p->consistencia ?? '' }}</td>
                        <td class="center">{{ $p->bacterias ?? '' }}</td>
                    </tr>
                    </tbody>
                </table>

                <div class="section">
                    <h3>Otros</h3>
                    <div class="box" style="min-height:20px;">{{ $p->otros ?? '' }}</div>
                </div>
            </div>

            <!-- COPROPARASITOLÓGICO -->
            <div class="section">
                <h3>Coproparasitológico</h3>

                @if($tipo === 'SERIADO')
                    <table>
                        <thead>
                        <tr>
                            <th style="width:33%;">DESCRIPCIÓN MUESTRA 1</th>
                            <th style="width:33%;">DESCRIPCIÓN MUESTRA 2</th>
                            <th style="width:34%;">DESCRIPCIÓN MUESTRA 3</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>{{ $p->descripcion_muestra_1 ?? '' }}</td>
                            <td>{{ $p->descripcion_muestra_2 ?? '' }}</td>
                            <td>{{ $p->descripcion_muestra_3 ?? '' }}</td>
                        </tr>
                        </tbody>
                    </table>
                @else
                    <table>
                        <thead>
                        <tr>
                            <th>DESCRIPCIÓN MUESTRA</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="height:26px;">{{ $p->descripcion_muestra ?? '' }}</td>
                        </tr>
                        </tbody>
                    </table>
                @endif

                <table style="margin-top:4px;">
                    <thead>
                    <tr>
                        <th style="width:20%;">SANGRE OCULTA</th>
                        <th style="width:20%;">PRUEBA RÁPIDA ROTAVIRUS</th>
                        <th style="width:20%;">MOCO FECAL</th>
                        <th style="width:20%;">MOCO FECAL DESCRIPCION</th>
                        <th style="width:20%;">TEST BENEDICT</th>
                        <th style="width:20%;">REACCIÓN</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="center">{{ $p->sangre_oculta ?? '' }}</td>
                        <td class="center">{{ $p->prueba_rapida_rotavirus ?? '' }}</td>
                        <td class="center">{{ $p->moco_fecal_positivo ?? '' }}</td>
                        <td class="center">{{ $p->moco_fecal ?? '' }}</td>
                        <td class="center">{{ $p->test_benedict ?? '' }}</td>
                        <td class="center">{{ $p->reaccion ?? '' }}</td>
                    </tr>
                    </tbody>
                </table>

                <div class="section">
                    <h3>Otros exámenes</h3>
                    <div class="box" style="min-height:22px;">{{ $p->otros_examenes ?? '' }}</div>
{{--                    <div class="box" style="min-height:22px;">{{ $p->otros_examenes_otros ?? '' }}</div> si tien mostrar si es otr--}}
                    @if($p->otros_examenes=='OTROS' || $p->otros_examenes=='Otros' || $p->otros_examenes=='otros')
                        <div class="box" style="min-height:22px;">
                            {{ $p->otros_examenes_otros ?? '' }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- FIRMAS -->
            <table class="no-border" style="margin-top:6px;">
                <tr>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">Firma / Sello</span>
                    </td>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">
                            {{$p->user? $p->user->name:'---'}} <br>
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
                </tr>
            </table>

        </div>
    @endforeach
</div>

</body>
</html>
