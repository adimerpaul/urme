<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <style>
        @page { size: letter ; margin: 10px 12px; }
        * { box-sizing: border-box; }

        body{
            margin:0; padding:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color:#111;
            line-height: 1;
        }

        /* 2 columnas */
        .sheet{ width:100%; overflow:hidden; }
        .half{ width:48%; float:left; overflow:hidden; padding:0; }

        /* Ajuste */
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

        /* Líneas / cajas */
        .hr { border-top: 1.8px solid #111; margin: 2px 0; }
        .box { border: 1px solid #111; padding: 3px 4px; }
        .small { font-size: 12px; }
        .center { text-align:center; }
        .right { text-align:right; }
        .bold{ font-weight:700; }

        /* Tablas */
        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        th, td{ border:1px solid #111; padding: 1.8px 3px; vertical-align: middle; }
        th{ background:#f2f2f2; font-weight:700; font-size: 12px; }
        .no-border td, .no-border th{ border:none; padding:0; }

        /* Form */
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

        img{ max-width: 100%; }
        .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* Estilo tabla “lista” como el reporte */
        .list th, .list td{ border:none; }
        .list thead th{
            text-transform: uppercase;
            background: transparent;
            font-size: 7.6px;
            padding-top: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid #cfcfcf;
        }
        .list tbody td{
            border-bottom: 1px solid #e6e6e6;
            padding: 3.2px 2px;
            font-size: 8px;
        }
        .col-path{ width:54%; font-style: italic; color:#2b2b2b; }
        .col-res{ width:23%; text-align:center; }
        .col-ref{ width:23%; text-align:right; color:#555; }

        .res-pos{ font-weight:700; color: #c00; }
        .res-neg{ color:#666; }

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
    // Helpers simples
    $val = fn($v, $d='—') => ($v === null || $v === '') ? $d : $v;

    $norm = function($v){
        $v = strtoupper(trim((string)$v));
        if ($v === 'DETECTADO') return 'DETECTADO';
        if ($v === 'NO DETECTADO' || $v === 'NO_DETECTADO' || $v === 'NEGATIVO' || $v === '') return 'NO DETECTADO';
        return $v ?: 'NO DETECTADO';
    };

    $isPos = fn($v) => $norm($v) === 'DETECTADO';

    $areaTitulo = 'BIOLOGÍA MOLECULAR';
//    $metodo = 'QPCR - RT EN TIEMPO REAL';

    $tipoMuestra = $solicitud->muestra_identificacion ?? 'HISOPADO COMBINADO';

    // filas (como la imagen)
    $rows = [
        ['Virus Sincitial Respiratorio A, B', 'vrs_ab'],
        ['Influenza B', 'influenza_b'],
        ['Influenza A', 'influenza_a'],
        ['SARS CoV-2', 'sars_cov_2'],
        ['Streptococcus pyogenes', 'streptococcus_pyogenes'],
        ['Adenovirus', 'adenovirus'],
        ['Rhinovirus', 'rhinovirus'],
        ['Coronavirus 229E/OC43', 'coronavirus_229e_oc43'],
        ['Parainfluenza 1,2', 'parainfluenza_1_2'],
        ['Coronavirus NL63/HKU1', 'coronavirus_nl63_hku1'],
        ['Parainfluenza 3,4', 'parainfluenza_3_4'],
        ['Haemophilus influenzae', 'haemophilus_influenzae'],
        ['Bordetella pertussis', 'bordetella_pertussis'],
        ['Streptococcus pneumoniae', 'streptococcus_pneumoniae'],
        ['Bocavirus', 'bocavirus'],
        ['Mycoplasma pneumoniae', 'mycoplasma_pneumoniae'],
        ['Metapneumovirus', 'metapneumovirus'],
        ['Enterovirus', 'enterovirus'],
        ['Legionella pneumophila', 'legionella_pneumophila'],
    ];
@endphp

<div >
{{--    class="sheet clearfix"--}}
    @foreach(['left'] as $side)
{{--        class="half half-{{ $side }}"--}}
        <div  style="margin: 20px 20px;">

            {!! view('components.headerSinCabecera', ['solicitud' => $solicitud])->render() !!}
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
                        {{ $panel->numeracion ?? '-' }}
                    </td>
                </tr>
            </table>

            <!-- ===== TITULO ===== -->
            <div class="center" style="margin-top:10px; font-weight:700; font-size:12px;">
                RESULTADOS PANEL RESPIRATORIO POR PCR
            </div>

            <div class="center muted small" style="margin-top:5px;">
                <b>Método:</b> Reacción en Cadena de la Polimerasa en Tiempo Real
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <b>Equipo:</b> {{ $panel->equipo ?? '-' }}
            </div>

            <!-- ===== TABLA RESULTADOS (lista como imagen) ===== -->
            <div style="margin-top:6px;">
                <table class="list">
                    <thead>
                    <tr>
                        <th class="col-path" style="text-align:left;font-size: 11px">PATÓGENO</th>
                        <th class="col-res center" style="font-size: 11px">RESULTADO</th>
                        <th class="col-ref right" style="font-size: 11px">VALORES DE REFERENCIA</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $r)
                        @php
                            $label = $r[0];
                            $key = $r[1];
                            $value = $norm($panel?->{$key} ?? 'NO DETECTADO');
                        @endphp
                        <tr>
                            <td class="col-path" style="font-size: 11px">{{ $label }}</td>
                            <td class="col-res {{ $isPos($value) ? 'res-pos' : 'res-neg' }}" style="font-size: 11px">{{ $value }}</td>
                            <td class="col-ref" style="font-size: 11px">NO DETECTADO</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <!-- ===== INTERPRETACIÓN ===== -->
            <div class="small" style="margin-top:8px;">
                <span class="bold">* Interpretación de resultados:</span>
                NO DETECTADO (NEGATIVO); DETECTADO (POSITIVO)
            </div>

            <!-- ===== OBS ===== -->
            <div style="margin-top:6px;">
                <span class="bold small">OBSERVACIONES:</span>
                <div class="box" style="min-height:24px;">{{ $panel->observaciones ?? '' }}</div>
            </div>

            <!-- ===== FIRMAS ===== -->
            <table class="no-border" style="margin-top:6px;">
                <tr>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">Firma / Sello</span>
                    </td>
                    <td class="center" style="width:33%;">
                        ___________________________<br>
                        <span class="muted small">
                            {{$panel->user? $panel->user->name:'---'}} <br>
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
