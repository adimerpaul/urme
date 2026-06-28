{{-- resources/views/pdf/uroanalisis.blade.php --}}
    <!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: legal; margin: 8px 10px; }
        * { box-sizing: border-box; }
        body{
            margin:0; padding:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color:#111;
            line-height: 0.9;
        }

        /* Layout 1 columna (la derecha estaba comentada y deja la izq. al 50%
           sobresalido). Ahora ocupa el ancho completo del legal. */
        table.layout { width:100%; border-collapse: collapse; table-layout: fixed; }
        td.col { width:100%; vertical-align: top; padding:0 6px; }

        /* Evitar saltos raros */
        table { page-break-inside: avoid; }
        tr, td, th { page-break-inside: avoid; }

        .title { font-weight:700; font-size: 13px; text-align:center; }
        .subtitle { font-size: 13px; text-align:center; margin-top: 1px; }
        .muted { color:#555; }
        .small { font-size: 13px; }
        .hr { border-top: 1.6px solid #111; margin: 2px 0; }

        table.inner{ width:100%; border-collapse: collapse; table-layout: fixed; }
        table.inner th, table.inner td{
            border:1px solid #111;
            padding: 3px 4px;
            vertical-align: middle;
        }
        table.inner th{
            background:#f2f2f2;
            font-weight:700;
            font-size: 13px;
        }

        .no-border td, .no-border th{ border:none; padding:0; }

        table.grid { width:100%; border-collapse: collapse; table-layout: fixed; }
        table.grid td{
            border:none;
            padding: 2px 3px 2px 0;
            vertical-align: bottom;
            font-size: 8px;
        }

        .label{ font-weight:700; }
        .line{
            border-bottom: 1px solid #111;
            height: 11px;
            padding: 0 3px;
            font-size: 8px;
        }

        .section{ margin-top: 3px; }
        .section h3{
            margin: 0 0 2px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        .box { border: 1px solid #111; padding: 3px 4px; min-height: 16px; }

        .center { text-align:center; }
        .right { text-align:right; }
    </style>
</head>
<body>
@php $u = $uroanalisis; @endphp

<table class="layout">
    <tr>
        {{-- COLUMNA IZQUIERDA --}}
        <td class="col">
            @includeWhen(false, 'noop') {{-- solo para evitar warnings en algunos editores --}}
            @php $side = 'left'; @endphp
            {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud'=>$u->created_at])->render() !!}

            <div class="section center" style="margin-top:4px; font-weight:700; font-size:11px;">
                UROANÁLISIS
            </div>

            {{-- MATERIAL / MÉTODO --}}
            <div class="section">
                <table class="inner">
                    <thead>
                    <tr>
                        <th style="width:30%;">MATERIAL DE ENSAYO</th>
                        <th style="width:70%;">MÉTODO</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="center">{{ $u->material_ensayo ?? 'ORINA' }}</td>
                        <td class="center">{{ $u->metodo ?? '' }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <table>
                <tr>
                    <td valign="top">
                        {{-- EXAMEN FÍSICO --}}
                        <div class="section">
                            <h3>Examen físico</h3>
                            <table class="inner">
                                <thead>
                                <tr>
                                    <th style="width:30%;">EXAMEN</th>
                                    <th style="width:30%;">RES.</th>
                                    <th style="width:20%;">UNIDADES</th>
                                    <th style="width:20%;">RANGO</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr><td>Cantidad</td><td class="center">{{ $u->cantidad ?? '' }}</td><td class="center">ml</td><td class="center">*</td></tr>
                                <tr><td>Color</td><td class="center">{{ $u->color ?? '' }}</td><td class="center">*</td><td class="center">Amarillo</td></tr>
                                <tr><td>Olor</td><td class="center">{{ $u->olor ?? '' }}</td><td class="center">*</td><td class="center">Sui-generis</td></tr>
                                <tr><td>Aspecto</td><td class="center">{{ $u->aspecto ?? '' }}</td><td class="center">*</td><td class="center">Límpido</td></tr>
                                <tr><td>Reacción (pH)</td><td class="center">{{ $u->reaccion ?? '' }}</td><td class="center">*</td><td class="center">pH 6.0 ácido</td></tr>
                                <tr><td>Densidad</td><td class="center">{{ $u->densidad ?? '' }}</td><td class="center">*</td><td class="center">1.025</td></tr>
                                <tr><td>Espuma</td><td class="center">{{ $u->espuma ?? '' }}</td><td class="center">*</td><td class="center">Blanco fugaz</td></tr>
                                <tr><td>Sedimento</td><td class="center">{{ $u->sedimento ?? '' }}</td><td class="center">*</td><td class="center">Escaso</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                    <td>
                        {{-- MICROSCÓPICO --}}
                        <div class="section">
                            <h3>Examen microscópico (sedimento)</h3>
                            <table class="inner">
                                <thead>
                                <tr>
                                    <th style="width:30%;">EXAMEN</th>
                                    <th style="width:30%;">SEDIMENTO</th>
                                    <th style="width:20%;">UNIDADES</th>
                                    <th style="width:20%;">RANGO</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr><td>Leucocitos</td><td class="center">{{ $u->leucocitos ?? '' }}</td><td class="center">xcampo/uL</td><td class="center">0-1</td></tr>
                                <tr><td>Hematies</td><td class="center">{{ $u->hematies ?? '' }}</td><td class="center">xcampo/uL</td><td class="center">0-1</td></tr>

                                <tr>
                                    <td>Morfología eritrocitaria</td>
                                    <td class="center">
                                        {{ $u->morfologia_eritrocitaria ?? '' }}
                                        @if(!empty($u->valor_morfologia)) ({{ $u->valor_morfologia }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">*</td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td class="center">
                                        {{ $u->morfologia_eritrocitaria2 ?? '' }}
                                        @if(!empty($u->valor_morfologia2)) ({{ $u->valor_morfologia2 }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">*</td>
                                </tr>

                                <tr><td>Bacterias</td><td class="center">{{ $u->bacterias ?? '' }}</td><td class="center">*</td><td class="center">Escaso</td></tr>
                                <tr><td>Filamento mucoide</td><td class="center">{{ $u->filamento_mucoide ?? '' }}</td><td class="center">*</td><td class="center">*</td></tr>

                                <tr>
                                    <td>Cilindros</td>
                                    <td class="center">
                                        {{ $u->cilindros ?? '' }}
                                        @if(!empty($u->valor_cilindros)) ({{ $u->valor_cilindros }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">#</td>
                                </tr>
                                @if(!empty($u->cilindros2) || !empty($u->cilindros_valor2))
                                <tr>
                                    <td></td>
                                    <td class="center">
                                        {{ $u->cilindros2 ?? '' }}
                                        @if(!empty($u->cilindros_valor2)) ({{ $u->cilindros_valor2 }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">#</td>
                                </tr>
                                @endif

                                <tr>
                                    <td>Células epiteliales</td>
                                    <td class="center">
                                        {{ $u->celulas ?? '' }}
                                        @if(!empty($u->valor_celulas)) ({{ $u->valor_celulas }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">#</td>
                                </tr>
                                @if(!empty($u->celulas_epiteliales2) || !empty($u->celulas_epiteliales_valor2))
                                <tr>
                                    <td></td>
                                    <td class="center">
                                        {{ $u->celulas_epiteliales2 ?? '' }}
                                        @if(!empty($u->celulas_epiteliales_valor2)) ({{ $u->celulas_epiteliales_valor2 }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">#</td>
                                </tr>
                                @endif

                                <tr>
                                    <td>Cristales</td>
                                    <td class="center">
                                        {{ $u->cristales ?? '' }}
                                        @if(!empty($u->valor_cristales)) ({{ $u->valor_cristales }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">#</td>
                                </tr>
                                @if(!empty($u->cristales2) || !empty($u->cristales_valor2))
                                <tr>
                                    <td></td>
                                    <td class="center">
                                        {{ $u->cristales2 ?? '' }}
                                        @if(!empty($u->cristales_valor2)) ({{ $u->cristales_valor2 }}) @endif
                                    </td>
                                    <td class="center">*</td>
                                    <td class="center">#</td>
                                </tr>
                                @endif

                                @if(!empty($u->otros))
                                    <tr>
                                        <td>Otros</td>
                                        <td class="center">{{ $u->otros }}</td>
                                        <td class="center">*</td>
                                        <td class="center">*</td>
                                    </tr>
                                @endif
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>



            {{-- QUÍMICO --}}
            <div class="section">
                <h3>Examen químico</h3>
                <table class="inner">
                    <thead>
                    <tr>
                        <th style="width:25%;">EXAMEN</th>
                        <th style="width:25%;">RES.</th>
                        <th style="width:25%;">EXAMEN</th>
                        <th style="width:25%;">RES.</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr><td>Proteínas</td><td class="center">{{ $u->proteinas ?? '' }}</td><td>Glucosa</td><td class="center">{{ $u->glucosa ?? '' }}</td></tr>
{{--                    <tr></tr>--}}
                    <tr><td>Sangre</td><td class="center">{{ $u->sangre ?? '' }}</td><td>Cetonas</td><td class="center">{{ $u->cetonas ?? '' }}</td></tr>
{{--                    <tr></tr>--}}
                    <tr><td>Bilirrubina</td><td class="center">{{ $u->bilirrubina ?? '' }}</td><td>Urobilinógeno</td><td class="center">{{ $u->urobilinogeno ?? '' }}</td></tr>
{{--                    <tr></tr>--}}
                    <tr><td>Nitritos</td><td class="center">{{ $u->nitritos ?? '' }}</td><td></td><td></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>Observaciones</h3>
                <div class="box">{{ $u->observaciones ?? '' }}</div>
            </div>

            <table class="no-border" style="width:100%; margin-top:6px;">
                <tr>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">Firma / Sello</span>
                    </td>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">
                            {{$u->user? $u->user->name:'---'}} <br>
                            Bioquímico(a) / Responsable
                        </span>
                    </td>
                    <td class="center" style="width:34%;">
                        @if(!empty($qrSvgBase64))
                            <img
                                src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}"
                                style="width:55px; height:55px;"
                                alt="QR"
                            >
                        @endif
                    </td>
                </tr>
            </table>
            {{-- ====== FIN COLUMNA ====== --}}
        </td>

        {{-- COLUMNA DERECHA (MISMO CONTENIDO DUPLICADO) --}}
{{--        <td class="col">--}}
{{--            @php $side = 'right'; @endphp--}}

{{--            <table class="no-border" style="width:100%;">--}}
{{--                <tr>--}}
{{--                    <td style="width:15%;">--}}
{{--                        <img src="{{ public_path('img/logo-hospital.png') }}" style="width:58px;">--}}
{{--                    </td>--}}

{{--                    <td>--}}
{{--                        <div class="title">HOSPITAL GENERAL SAN JUAN DE DIOS ORURO BLOQUE CENTRAL</div>--}}
{{--                        <div class="subtitle muted">LABORATORIO DE ANÁLISIS CLÍNICO - MICROBIOLÓGICO</div>--}}
{{--                        <div class="subtitle muted small">Dirección: San Felipe entre 6 de Octubre y Tarija</div>--}}
{{--                        <div class="subtitle muted small">REGISTRO CONALAB: 001 &nbsp;&nbsp; REGISTRO CODELAB: 000004</div>--}}
{{--                    </td>--}}

{{--                    <td style="width:15%;" class="right">--}}
{{--                        <img src="{{ public_path('img/logo-labo.png') }}" style="width:58px;">--}}
{{--                    </td>--}}
{{--                </tr>--}}
{{--            </table>--}}

{{--            <div class="hr"></div>--}}

{{--            <table class="grid" style="margin-top:2px;">--}}
{{--                <tr>--}}
{{--                    <td style="width:18%"><span class="label">CÓDIGO:</span></td>--}}
{{--                    <td style="width:32%"><div class="line">{{ $solicitud->codigo ?? $solicitud->id }}</div></td>--}}
{{--                    <td style="width:20%"><span class="label">NRO. REGISTRO:</span></td>--}}
{{--                    <td style="width:30%"><div class="line">{{ $solicitud->nro_registro ?? '-' }}</div></td>--}}
{{--                </tr>--}}

{{--                <tr>--}}
{{--                    <td><span class="label">PACIENTE:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->paciente_nombre ?? '-' }}</div></td>--}}
{{--                    <td><span class="label">EDAD:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->paciente_edad ?? '-' }}</div></td>--}}
{{--                </tr>--}}

{{--                <tr>--}}
{{--                    <td><span class="label">MEDICO SOL.:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->doctor_nombre ?? '-' }}</div></td>--}}
{{--                    <td><span class="label">SEXO:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->paciente_genero ?? '-' }}</div></td>--}}
{{--                </tr>--}}

{{--                <tr>--}}
{{--                    <td><span class="label">FECHA SOL. MEDICO:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->fecha_solicitud ?? '-' }}</div></td>--}}
{{--                    <td><span class="label">TIPO MUESTRA:</span></td>--}}
{{--                    <td><div class="line">ORINA</div></td>--}}
{{--                </tr>--}}

{{--                <tr>--}}
{{--                    <td><span class="label">EST. DE SALUD:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->establecimiento_salud ?? '-' }}</div></td>--}}
{{--                    <td><span class="label">CI:</span></td>--}}
{{--                    <td><div class="line">{{ $solicitud->paciente_ci ?? '-' }}</div></td>--}}
{{--                </tr>--}}
{{--            </table>--}}

{{--            <div class="section center" style="margin-top:4px; font-weight:700; font-size:9px;">--}}
{{--                UROANÁLISIS--}}
{{--            </div>--}}

{{--            <div class="section">--}}
{{--                <table class="inner">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th style="width:30%;">MATERIAL DE ENSAYO</th>--}}
{{--                        <th style="width:70%;">MÉTODO</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    <tr>--}}
{{--                        <td class="center">{{ $u->material_ensayo ?? 'ORINA' }}</td>--}}
{{--                        <td class="center">{{ $u->metodo ?? '' }}</td>--}}
{{--                    </tr>--}}
{{--                    </tbody>--}}
{{--                </table>--}}
{{--            </div>--}}

{{--            <div class="section">--}}
{{--                <h3>Examen físico</h3>--}}
{{--                <table class="inner">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th style="width:30%;">EXAMEN</th>--}}
{{--                        <th style="width:30%;">RES.</th>--}}
{{--                        <th style="width:20%;">UNIDADES</th>--}}
{{--                        <th style="width:20%;">RANGO</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    <tr><td>Cantidad</td><td class="center">{{ $u->cantidad ?? '' }}</td><td class="center">ml</td><td class="center">*</td></tr>--}}
{{--                    <tr><td>Color</td><td class="center">{{ $u->color ?? '' }}</td><td class="center">*</td><td class="center">Amarillo</td></tr>--}}
{{--                    <tr><td>Olor</td><td class="center">{{ $u->olor ?? '' }}</td><td class="center">*</td><td class="center">Sui-generis</td></tr>--}}
{{--                    <tr><td>Aspecto</td><td class="center">{{ $u->aspecto ?? '' }}</td><td class="center">*</td><td class="center">Límpido</td></tr>--}}
{{--                    <tr><td>Reacción (pH)</td><td class="center">{{ $u->reaccion ?? '' }}</td><td class="center">*</td><td class="center">pH 6.0 ácido</td></tr>--}}
{{--                    <tr><td>Densidad</td><td class="center">{{ $u->densidad ?? '' }}</td><td class="center">*</td><td class="center">1.025</td></tr>--}}
{{--                    <tr><td>Espuma</td><td class="center">{{ $u->espuma ?? '' }}</td><td class="center">*</td><td class="center">Blanco fugaz</td></tr>--}}
{{--                    <tr><td>Sedimento</td><td class="center">{{ $u->sedimento ?? '' }}</td><td class="center">*</td><td class="center">Escaso</td></tr>--}}
{{--                    </tbody>--}}
{{--                </table>--}}
{{--            </div>--}}

{{--            <div class="section">--}}
{{--                <h3>Examen microscópico (sedimento)</h3>--}}
{{--                <table class="inner">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th style="width:30%;">EXAMEN</th>--}}
{{--                        <th style="width:30%;">SEDIMENTO</th>--}}
{{--                        <th style="width:20%;">UNIDADES</th>--}}
{{--                        <th style="width:20%;">RANGO</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    <tr><td>Leucocitos</td><td class="center">{{ $u->leucocitos ?? '' }}</td><td class="center">xcampo/uL</td><td class="center">0-1</td></tr>--}}
{{--                    <tr><td>Hematies</td><td class="center">{{ $u->hematies ?? '' }}</td><td class="center">xcampo/uL</td><td class="center">0-1</td></tr>--}}

{{--                    <tr>--}}
{{--                        <td>Morfología eritrocitaria</td>--}}
{{--                        <td class="center">--}}
{{--                            {{ $u->morfologia_eritrocitaria ?? '' }}--}}
{{--                            @if(!empty($u->valor_morfologia)) ({{ $u->valor_morfologia }}) @endif--}}
{{--                        </td>--}}
{{--                        <td class="center">*</td>--}}
{{--                        <td class="center">*</td>--}}
{{--                    </tr>--}}

{{--                    <tr><td>Bacterias</td><td class="center">{{ $u->bacterias ?? '' }}</td><td class="center">*</td><td class="center">Escaso</td></tr>--}}
{{--                    <tr><td>Filamento mucoide</td><td class="center">{{ $u->filamento_mucoide ?? '' }}</td><td class="center">*</td><td class="center">*</td></tr>--}}

{{--                    <tr>--}}
{{--                        <td>Cilindros</td>--}}
{{--                        <td class="center">--}}
{{--                            {{ $u->cilindros ?? '' }}--}}
{{--                            @if(!empty($u->valor_cilindros)) ({{ $u->valor_cilindros }}) @endif--}}
{{--                        </td>--}}
{{--                        <td class="center">*</td>--}}
{{--                        <td class="center">#</td>--}}
{{--                    </tr>--}}

{{--                    <tr>--}}
{{--                        <td>Células epiteliales</td>--}}
{{--                        <td class="center">--}}
{{--                            {{ $u->celulas ?? '' }}--}}
{{--                            @if(!empty($u->valor_celulas)) ({{ $u->valor_celulas }}) @endif--}}
{{--                        </td>--}}
{{--                        <td class="center">*</td>--}}
{{--                        <td class="center">#</td>--}}
{{--                    </tr>--}}

{{--                    <tr>--}}
{{--                        <td>Cristales</td>--}}
{{--                        <td class="center">--}}
{{--                            {{ $u->cristales ?? '' }}--}}
{{--                            @if(!empty($u->valor_cristales)) ({{ $u->valor_cristales }}) @endif--}}
{{--                        </td>--}}
{{--                        <td class="center">*</td>--}}
{{--                        <td class="center">#</td>--}}
{{--                    </tr>--}}

{{--                    @if(!empty($u->otros))--}}
{{--                        <tr>--}}
{{--                            <td>Otros</td>--}}
{{--                            <td class="center">{{ $u->otros }}</td>--}}
{{--                            <td class="center">*</td>--}}
{{--                            <td class="center">*</td>--}}
{{--                        </tr>--}}
{{--                    @endif--}}
{{--                    </tbody>--}}
{{--                </table>--}}
{{--            </div>--}}

{{--            <div class="section">--}}
{{--                <h3>Examen químico</h3>--}}
{{--                <table class="inner">--}}
{{--                    <thead>--}}
{{--                    <tr>--}}
{{--                        <th style="width:50%;">EXAMEN</th>--}}
{{--                        <th style="width:50%;">RES.</th>--}}
{{--                    </tr>--}}
{{--                    </thead>--}}
{{--                    <tbody>--}}
{{--                    <tr><td>Proteínas</td><td class="center">{{ $u->proteinas ?? '' }}</td></tr>--}}
{{--                    <tr><td>Glucosa</td><td class="center">{{ $u->glucosa ?? '' }}</td></tr>--}}
{{--                    <tr><td>Sangre</td><td class="center">{{ $u->sangre ?? '' }}</td></tr>--}}
{{--                    <tr><td>Cetonas</td><td class="center">{{ $u->cetonas ?? '' }}</td></tr>--}}
{{--                    <tr><td>Bilirrubina</td><td class="center">{{ $u->bilirrubina ?? '' }}</td></tr>--}}
{{--                    <tr><td>Urobilinógeno</td><td class="center">{{ $u->urobilinogeno ?? '' }}</td></tr>--}}
{{--                    <tr><td>Nitritos</td><td class="center">{{ $u->nitritos ?? '' }}</td></tr>--}}
{{--                    </tbody>--}}
{{--                </table>--}}
{{--            </div>--}}

{{--            <div class="section">--}}
{{--                <h3>Observaciones</h3>--}}
{{--                <div class="box">{{ $u->observaciones ?? '' }}</div>--}}
{{--            </div>--}}

{{--            <table class="no-border" style="width:100%; margin-top:6px;">--}}
{{--                <tr>--}}
{{--                    <td class="center" style="width:33%;">--}}
{{--                        ___________________________<br>--}}
{{--                        <span class="muted small">Firma / Sello</span>--}}
{{--                    </td>--}}
{{--                    <td class="center" style="width:33%;">--}}
{{--                        ___________________________<br>--}}
{{--                        <span class="muted small">Bioquímico(a) / Responsable</span>--}}
{{--                    </td>--}}
{{--                    <td class="center" style="width:34%;">--}}
{{--                        @if(!empty($qrSvgBase64))--}}
{{--                            <img--}}
{{--                                src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}"--}}
{{--                                style="width:55px; height:55px;"--}}
{{--                                alt="QR"--}}
{{--                            >--}}
{{--                        @endif--}}
{{--                    </td>--}}
{{--                </tr>--}}
{{--            </table>--}}
{{--        </td>--}}
    </tr>
</table>

</body>
</html>
