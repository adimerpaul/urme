<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: legal; margin: 20px 20px; }
        * { box-sizing: border-box; }

        body{
            margin:0; padding:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color:#111;
            line-height: 1;
        }

        .title { font-weight:700; font-size: 10.2px; text-align:center; }
        .subtitle { font-size: 8px; text-align:center; margin-top: 1px; }
        .muted { color:#555; }
        .small { font-size: 7.6px; }
        .center { text-align:center; }
        .right { text-align:right; }

        .hr { border-top: 1.8px solid #111; margin: 2px 0; }

        .section-title{
            margin-top: 2px;
            margin-bottom: 1px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .25px;
        }

        table{ width:100%; border-collapse: collapse; table-layout: fixed; }
        th, td{ border:1px solid #111; padding: 1px 2px; vertical-align: middle; }
        th{ background:#f2f2f2; font-weight:700; font-size: 7px; }
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

        img{ max-width: 100%; }
        .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }
        .out-range{ color: #c10015; font-weight: 700; }

        /* ✅ layout estable en DomPDF */
        .sheet-table { width:100%; border-collapse: collapse; }
        .sheet-table td { border:none; vertical-align: top; }
        .half-cell { width:50%; padding: 0 6px; }

        /* evita cortes raros */
        .block { page-break-inside: avoid; }
    </style>
</head>

<body>

@php
    /* =========================
       HELPERS: RANGOS
       ========================= */
    $normRango = function($s) {
        $s = mb_strtolower(trim((string)($s ?? '')));
        if (function_exists('iconv')) {
            $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($t !== false && $t !== '') $s = $t;
        }
        // Eliminar marcas remanentes y normalizar espacios
        $s = preg_replace('/[`\'"^~]/u', '', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        return $s;
    };

    $rango = function($nombre) use ($rangos, $normRango) {
        if (!$rangos) return null;
        $n = $normRango($nombre);
        foreach ($rangos as $r) {
            if ($normRango($r->rango_nombre ?? '') === $n) return $r;
        }
        return null;
    };

    $rangoTexto = function($nombre) use ($rango) {
        $r = $rango($nombre);
        if (!$r) return '';
        return $r->interpretacion ?? '';
    };

    $rangoUnidad = function($nombre) use ($rango) {
        $r = $rango($nombre);
        return $r->unidad ?? '';
    };

    $outOfRange = function($nombre, $valor) use ($rango) {
        if ($valor === null || $valor === '') return false;
        $r = $rango($nombre);
        if (!$r) return false;

        $num = floatval($valor);

        if (!is_null($r->rango_minimo) && $num < $r->rango_minimo) return true;
        if (!is_null($r->rango_maximo) && $num > $r->rango_maximo) return true;

        return false;
    };

    /* =========================
       HELPERS: SERVICIOS
       ========================= */
    $norm = function($v){
        $v = (string)($v ?? '');
        $v = preg_replace('/\s+/u', ' ', trim($v));
        return mb_strtolower($v);
    };

    $canServicios = function($can) use ($solicitud, $norm) {
        $servicios = $solicitud->servicios ?? [];
        if (!is_iterable($servicios)) return false;

        $targets = is_array($can) ? $can : [$can];
        $wanted = array_map($norm, $targets);

        foreach ($servicios as $s) {
            $sn = $norm($s->nombre ?? '');
            if (in_array($sn, $wanted, true)) return true;
        }
        return false;
    };

    /* =========================
       FLAGS DE SECCIÓN
       ========================= */
    $showHemograma = true;
        // ✅ aquí YA NO pongo "|| true" para que se oculte si no está el servicio

    $showIndices = $canServicios(['ÍNDICES HEMATIMÉTRICOS', 'HEMOGRAMA COMPLETO+ PLAQUETAS']);
    $showDiferencial = $canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS');

    $showCoagulograma = $canServicios([
        'COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)',
        'TIEMPO DE PROTROMBINA (TP)',
        'TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)',
        'FIBRINÓGENO',
        'ERITROSEDIMENTACIÓN (VSG- VES)'
    ]);

    $showOtros = $canServicios(['RECUENTO DE RETICULOCITOS']);
    $showFrotis = $canServicios('FROTIS SANGUÍNEO/LEUCOGRAMA');

    /* =========================
       RENDER 1 COPIA (para duplicar)
       ========================= */
    $renderHalf = function() use (
        $solicitud, $hematologia, $rangos, $qrSvgBase64,
        $canServicios, $outOfRange, $rangoTexto, $rangoUnidad,
        $showHemograma, $showIndices, $showDiferencial, $showCoagulograma, $showOtros, $showFrotis
    ){
        ob_start();
@endphp

<div class="block">
{{--    @include('components.header', ['solicitud' => $solicitud])--}}
    {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud'=>$hematologia->created_at])->render() !!}


    <div class="center" style="margin-top:20px; font-weight:700; font-size:12px;">
        HEMATOLOGÍA
    </div>

{{--    <div class="center muted small" style="margin-top:1px;">--}}
{{--        Equipo: {{ $hematologia->equipo == 'Otro' ? $hematologia->equipo_otro : $hematologia->equipo }} · Método: {{ $hematologia->metodo ?? '—' }}--}}
{{--    </div>--}}

    @if($showHemograma)
        <div class="section-title">
            Hemograma
            <div class="center muted small" style="margin-top:1px;">
                Equipo: {{ $hematologia->hemograma_metodo?? '-' }} · Método: {{ $hematologia->hemograma_equipo ?? '—' }}
            </div>
        </div>
        <table>
            <thead>
            <tr>
                <th style="width:20%;">ANALITO</th>
                <th style="width:18%;" class="center">RESULTADO</th>
                <th style="width:26%;" class="center">RANGO REF.</th>
                <th style="width:18%;" class="center">UNIDAD</th>
            </tr>
            </thead>
            <tbody>
            @php
//                $rowGR = $canServicios(['HEMOGRAMA COMPLETO+ PLAQUETAS','MORFOLOGÍA DE GLÓBULOS ROJOS']);
//                $rowGB = $canServicios('HEMOGRAMA COMPLETO+ PLAQUETAS');
//                $rowPL = $canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','HEMOGRAMA COMPLETO+ PLAQUETAS','RECUENTO DE PLAQUETAS']);
//                $rowHB = $canServicios(['HEMOGRAMA COMPLETO+ PLAQUETAS','HEMATOCRITO Y HEMOGLOBINA']);
//                $rowHT = $canServicios(['HEMOGRAMA COMPLETO+ PLAQUETAS','HEMATOCRITO Y HEMOGLOBINA']);
            $rowGR = true;
            $rowGB = true;
            $rowPL = true;
            $rowHB = true;
            $rowHT = true;
            @endphp

            @if($rowGR)
                <tr>
                    <td>Glóbulos rojos</td>
                    <td class="center {{ $outOfRange('Globulos Rojos', $hematologia->globulos_rojos ?? null) ? 'out-range' : '' }}">
                        {{ $hematologia->globulos_rojos ?? '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Globulos Rojos') }}</td>
                    <td class="center">{{ $rangoUnidad('Globulos Rojos') }}</td>
                </tr>
            @endif
            @if($rowGB)
                <tr>
                    <td>Glóbulos blancos</td>
                    <td class="center {{ $outOfRange('Globulos Blancos (Leucocitos)', $hematologia->globulos_blancos ?? null) ? 'out-range' : '' }}">
                        {{ $hematologia->globulos_blancos ?? '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Globulos Blancos (Leucocitos)') }}</td>
                    <td class="center">{{ $rangoUnidad('Globulos Blancos (Leucocitos)') }}</td>
                </tr>
            @endif
            @if($rowPL)
                <tr>
                    <td>Plaquetas</td>
                    <td class="center {{ $outOfRange('Plaquetas', $hematologia->plaquetas ?? null) ? 'out-range' : '' }}">
{{--                        {{ $hematologia->plaquetas ?? '' }} redondeado parte entera--}}
                        {{ $hematologia->plaquetas !== null ? $hematologia->plaquetas+0 : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Plaquetas') }}</td>
                    <td class="center">{{ $rangoUnidad('Plaquetas') }}</td>
                </tr>
            @endif
            @if($rowHB)
                <tr>
                    <td>Hemoglobina</td>
                    <td class="center {{ $outOfRange('Hemoglobina', $hematologia->hemoglobina ?? null) ? 'out-range' : '' }}">
{{--                        {{ $hematologia->hemoglobina ?? '' }}--}}
                        {{ $hematologia->hemoglobina !== null ? number_format($hematologia->hemoglobina, 1) : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Hemoglobina') }}</td>
                    <td class="center">{{ $rangoUnidad('Hemoglobina') }}</td>
                </tr>
            @endif
            @if($rowHT)
                <tr>
                    <td>Hematocrito</td>
                    <td class="center {{ $outOfRange('Hematocrito', $hematologia->hematocrito ?? null) ? 'out-range' : '' }}">
                        {{ $hematologia->hematocrito ?? '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Hematocrito') }}</td>
                    <td class="center">{{ $rangoUnidad('Hematocrito') }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    @endif

    @if($showIndices)
        <div class="section-title">Índices hematimétricos</div>
        <table>
            <thead>
            <tr>
                <th style="width:38%;">ANALITO</th>
                <th style="width:18%;" class="center">RESULTADO</th>
                <th style="width:26%;" class="center">RANGO REF.</th>
                <th style="width:18%;" class="center">UNIDAD</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>VCM</td>
                <td class="center {{ $outOfRange('V.C.M.', $hematologia->vcm ?? null) ? 'out-range' : '' }}">
{{--                    {{ $hematologia->vcm ?? '' }}--}}
                    {{ $hematologia->vcm !== null ? number_format($hematologia->vcm, 1) : '' }}
                </td>
                <td class="center">{{ $rangoTexto('V.C.M.') }}</td>
                <td class="center">{{ $rangoUnidad('V.C.M.') }}</td>
            </tr>
            <tr>
                <td>HBCM</td>
                <td class="center {{ $outOfRange('Hb.C.M.', $hematologia->hbcm ?? null) ? 'out-range' : '' }}">
{{--                    {{ $hematologia->hbcm ?? '' }}--}}
                    {{ $hematologia->hbcm !== null ? number_format($hematologia->hbcm, 1) : '' }}
                </td>
                <td class="center">{{ $rangoTexto('Hb.C.M.') }}</td>
                <td class="center">{{ $rangoUnidad('Hb.C.M.') }}</td>
            </tr>
            <tr>
                <td>CHCM</td>
                <td class="center {{ $outOfRange('CHCM', $hematologia->chcm ?? null) ? 'out-range' : '' }}">
{{--                    {{ $hematologia->chcm ?? '' }}--}}
                    {{ $hematologia->chcm !== null ? number_format($hematologia->chcm, 1) : '' }}
                </td>
                <td class="center">{{ $rangoTexto('CHCM') }}</td>
                <td class="center">{{ $rangoUnidad('CHCM') }}</td>
            </tr>
            </tbody>
        </table>
    @endif

    @if($showDiferencial)
        <div class="section-title">Recuento diferencial</div>
        <table>
            <thead>
            <tr>
                <th style="width:30%;">CÉLULA</th>
                <th style="width:14%;" class="center">%</th>
                <th style="width:18%;" class="center">ABS</th>
                <th style="width:18%;" class="center">RANGO %</th>
                <th style="width:20%;" class="center">RANGO ABS</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Basófilos</td>
                <td class="center">
{{--                    {{ $hematologia->basofilos_porcentaje ?? '' }}--}}
                    {{ $hematologia->basofilos_porcentaje !== null ? $hematologia->basofilos_porcentaje+0 : '' }}
                </td>
                <td class="center">{{ $hematologia->basofilos_absoluto ?? '' }}</td>
                <td class="center">{{ $rangoTexto('Basofilos') }}</td>
                <td class="center">{{ $rangoTexto('Basilos (Absoluto)') }}</td>
            </tr>
            <tr>
                <td>Eosinófilos</td>
                <td class="center">
{{--                    {{ $hematologia->eosinofilos_porcentaje ?? '' }}--}}
                    {{ $hematologia->eosinofilos_porcentaje !== null ? $hematologia->eosinofilos_porcentaje+0 : '' }}
                </td>
                <td class="center">{{ $hematologia->eosinofilos_absoluto ?? '' }}</td>
                <td class="center">{{ $rangoTexto('Eosinofilos') }}</td>
                <td class="center">{{ $rangoTexto('Eosinofilos (Absoluto)') }}</td>
            </tr>
            <tr>
                <td>Cayados</td>
                <td class="center">
{{--                    {{ $hematologia->cayados_porcentaje ?? '' }}--}}
                    {{ $hematologia->cayados_porcentaje !== null ? $hematologia->cayados_porcentaje+0 : '' }}
                </td>
                <td class="center">{{ $hematologia->cayados_absoluto ?? '' }}</td>
                <td class="center">{{ $rangoTexto('Cayados') }}</td>
                <td class="center">{{ $rangoTexto('Cayados (Absoluto)') }}</td>
            </tr>
            <tr>
                <td>Segmentados</td>
                <td class="center">
{{--                    {{ $hematologia->segmentados_porcentaje ?? '' }}--}}
                    {{ $hematologia->segmentados_porcentaje !== null ? $hematologia->segmentados_porcentaje+0 : '' }}
                </td>
                <td class="center">{{ $hematologia->segmentados_absoluto ?? '' }}</td>
                <td class="center">{{ $rangoTexto('Segmentados') }}</td>
                <td class="center">{{ $rangoTexto('Segmentados (Absoluto)') }}</td>
            </tr>
            <tr>
                <td>Linfocitos</td>
                <td class="center">
{{--                    {{ $hematologia->linfocitos_porcentaje ?? '' }}--}}
                    {{ $hematologia->linfocitos_porcentaje !== null ? $hematologia->linfocitos_porcentaje+0 : '' }}
                </td>
                <td class="center">{{ $hematologia->linfocitos_absoluto ?? '' }}</td>
                <td class="center">{{ $rangoTexto('Linfocitos') }}</td>
                <td class="center">{{ $rangoTexto('Linfocitos (Absoluto)') }}</td>
            </tr>
            <tr>
                <td>Monocitos</td>
                <td class="center">
                    {{ $hematologia->monocitos_porcentaje !== null ? $hematologia->monocitos_porcentaje+0 : '' }}
                </td>
                <td class="center">{{ $hematologia->monocitos_absoluto ?? '' }}</td>
                <td class="center">{{ $rangoTexto('Monocitos') }}</td>
                <td class="center">{{ $rangoTexto('Monocitos (Absoluto)') }}</td>
            </tr>
            @php
                $totalDif = collect([
                    $hematologia->basofilos_porcentaje,
                    $hematologia->eosinofilos_porcentaje,
                    $hematologia->cayados_porcentaje,
                    $hematologia->segmentados_porcentaje,
                    $hematologia->linfocitos_porcentaje,
                    $hematologia->monocitos_porcentaje,
                    $hematologia->blastos_porcentaje,
                    $hematologia->metamielocitos_porcentaje,
                    $hematologia->eritroblastos_porcentaje,
                ])->filter(fn($v) => $v !== null)->sum();
                $totalAbs = collect([
                    $hematologia->basofilos_absoluto,
                    $hematologia->eosinofilos_absoluto,
                    $hematologia->cayados_absoluto,
                    $hematologia->segmentados_absoluto,
                    $hematologia->linfocitos_absoluto,
                    $hematologia->monocitos_absoluto,
                    $hematologia->blastos_absoluto,
                    $hematologia->metamielocitos_absoluto,
                    $hematologia->eritroblastos_absoluto,
                ])->filter(fn($v) => $v !== null)->sum();
            @endphp
            <tr>
                <td style="font-weight:700; background:#f2f2f2;">TOTAL</td>
                <td class="center" style="font-weight:700; background:#f2f2f2;">
                    {{ $totalDif > 0 ? number_format($totalDif, 1) : '' }}
                </td>
                <td class="center" style="font-weight:700; background:#f2f2f2;">
                    {{ $totalAbs > 0 ? number_format($totalAbs, 2) : '' }}
                </td>
                <td colspan="2"></td>
            </tr>
            </tbody>
        </table>
    @endif

    @if($showCoagulograma)
        <div class="section-title">
            Coagulograma
            <div class="center muted small" style="margin-top:1px;">
                Equipo: {{ $hematologia->coagulograma_metodo?? '-' }} · Método: {{ $hematologia->coagulograma_equipo ?? '—' }}
            </div>
        </div>
        <table>
            <thead>
            <tr>
                <th style="width:20%;">PRUEBA</th>
                <th style="width:18%;" class="center">RESULTADO</th>
                <th style="width:22%;" class="center">RANGO</th>
                <th style="width:14%;" class="center">UNID.</th>
            </tr>
            </thead>
            <tbody>
            @if($canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO DE PROTROMBINA (TP)']))
                <tr>
                    <td>Tiempo de protrombina (TP)</td>
                    <td class="center">
                        {{ isset($hematologia->tiempo_protrombina)
                            ? number_format($hematologia->tiempo_protrombina, 1)
                            : '' }}
                    </td>
                    <td class="center">11 – 15</td>
                    <td class="center">seg</td>
                </tr>
                <tr>
                    <td>Actividad de protrombina</td>
                    <td class="center">
                        {{ isset($hematologia->actividad_protrombina)
                            ? number_format($hematologia->actividad_protrombina, 2)
                            : '' }}
                    </td>
                    <td class="center">70 – 100</td>
                    <td class="center">%</td>
                </tr>
                <tr>
                    <td>INR</td>
                    <td class="center">{{ $hematologia->inr ?? '' }}</td>
                    <td class="center">0.8 – 1.2</td>
                    <td class="center">-</td>
                </tr>
            @endif
            @if($canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)']))
                <tr>
                    <td>APTT</td>
                    <td class="center">
                        {{ isset($hematologia->aptt)
                            ? number_format($hematologia->aptt, 1)
                            : '' }}
                    </td>
                    <td class="center">24 – 35</td>
                    <td class="center">seg</td>
                </tr>
            @endif
            @if($canServicios('ERITROSEDIMENTACIÓN (VSG- VES)'))
                <tr>
                    <td>V.S.G.</td>
                    <td class="center">
                        {{ isset($hematologia->ves)
                            ? number_format($hematologia->ves, 1)
                            : '' }}
                    </td>
                    <td class="center">0 – 20</td>
                    <td class="center">mm/h</td>
                </tr>
            @endif
            @if($canServicios('FIBRINÓGENO'))
                <tr>
                    <td>Fibrinógeno</td>
                    <td class="center {{ $outOfRange('FIBRINOGENO', $hematologia->fibrinogeno ?? null) ? 'out-range' : '' }}">
                        {{ $hematologia->fibrinogeno !== null ? number_format($hematologia->fibrinogeno, 0) : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('FIBRINOGENO') ?: '200 - 400' }}</td>
                    <td class="center">{{ $rangoUnidad('FIBRINOGENO') ?: 'mg/dl' }}</td>
                </tr>
                <tr>
                    <td>Dímeros D</td>
                    <td class="center {{ $outOfRange('Dimeros D', $hematologia->dimeros_d ?? null) ? 'out-range' : '' }}">
                        {{ isset($hematologia->dimeros_d) ? number_format($hematologia->dimeros_d, 2) : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Dimeros D') ?: '0 - 0.40' }}</td>
                    <td class="center">{{ $rangoUnidad('Dimeros D') ?: 'ug/ml' }}</td>
                </tr>
            @endif
{{--            @if($canServicios(['COAGULOGRAMA (TP,RECUENTO DE PLAQUETAS, APTT)','TIEMPO PARCIAL DE TROMBOPLASTINA ACTIVADA (APTT)']))--}}

{{--            @endif--}}
            </tbody>
        </table>
    @endif

    @if($showOtros)
        <div class="section-title">Otros</div>
        <table>
            <thead>
            <tr>
                <th style="width:46%;">PRUEBA</th>
                <th style="width:18%;" class="center">RESULTADO</th>
                <th style="width:22%;" class="center">RANGO</th>
                <th style="width:14%;" class="center">UNID.</th>
            </tr>
            </thead>
            <tbody>
            @if($canServicios('RECUENTO DE RETICULOCITOS'))
                <tr>
                    <td>Reticulocitos</td>
                    <td class="center {{ $outOfRange('Reticulocitos', $hematologia->ipr2 ?? null) ? 'out-range' : '' }}">
                        {{ isset($hematologia->ipr2) ? number_format($hematologia->ipr2, 1) : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('Reticulocitos') }}</td>
                    <td class="center">{{ $rangoUnidad('Reticulocitos') }}</td>
                </tr>
                <tr>
                    <td>IRC</td>
                    <td class="center">
                        {{ isset($hematologia->rc) ? number_format($hematologia->rc, 2) : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('RC') }}</td>
                    <td class="center">{{ $rangoUnidad('RC') }}</td>
                </tr>
                <tr>
                    <td>IPR</td>
                    <td class="center {{ $outOfRange('IPR', $hematologia->ipr ?? null) ? 'out-range' : '' }}">
                        {{ isset($hematologia->ipr) ? number_format($hematologia->ipr, 2) : '' }}
                    </td>
                    <td class="center">{{ $rangoTexto('IPR') }}</td>
                    <td class="center">{{ $rangoUnidad('IPR') }}</td>
                </tr>
            @endif
            </tbody>
        </table>
    @endif

    @if($showFrotis)
        <div class="section-title">FROTIS DE SANGRE PERIFERICA</div>
        <table>
            <thead>
            <tr>
                <th style="width:33%;">SERIE ROJA</th>
                <th style="width:33%;">SERIE BLANCA</th>
                <th style="width:34%;">SERIE PLAQUETARIA</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td style="height:60px; vertical-align: top; font-size: 9px; padding:4px;">{!! nl2br(e($hematologia->serie_roja ?? '')) !!}</td>
                <td style="height:60px; vertical-align: top; font-size: 9px; padding:4px;">{!! nl2br(e($hematologia->serie_blanca ?? '')) !!}</td>
                <td style="height:60px; vertical-align: top; font-size: 9px; padding:4px;">{!! nl2br(e($hematologia->serie_plaqueta ?? '')) !!}</td>
            </tr>
            </tbody>
        </table>
    @endif

    @if($canServicios('GRUPO SANGUÍNEO Y FACTOR'))
        <div class="section-title">Grupo sanguíneo</div>
        <table>
            <tbody>
            <tr>
                <td><b>GRUPO SANGUÍNEO :</b> {{ $hematologia->grupo_sanguineo ?? '' }}</td>
                <td><b>Rh:</b> {{ $hematologia->factor_rh ?? '' }}</td>
            </tr>
            </tbody>
        </table>
    @endif
{{--    observaciones--}}
    <div class="section-title">Observaciones</div>
    <div class="box" style="min-height:20px; font-size:7.5px;">
        {!! nl2br(e($hematologia->observaciones ?? '')) !!}
    </div>

    <table class="no-border" style="margin-top:6px;">
        <tr>
            <td class="center" style="width:33%;">
                ___________________________<br>
                <span class="muted small">Firma / Sello</span>
            </td>
            <td class="center" style="width:33%;">
                ___________________________<br>
                <span class="muted small">
                    {{$hematologia->user? $hematologia->user->name:'---'}} <br>
                    Bioquímico(a) / Responsable
                </span>
            </td>
            <td class="center" style="width:34%;">
                @if(!empty($qrSvgBase64))
                    <img src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}" style="width:80px; height:80px;" alt="QR">
                @endif
            </td>
        </tr>
    </table>
</div>

@php
    return ob_get_clean();
};
@endphp

{{--<table class="sheet-table">--}}
{{--    <tr>--}}
{{--        <td class="half-cell">{!! $renderHalf() !!}</td>--}}
{{--        <td class="half-cell">{!! $renderHalf() !!}</td>--}}
{{--    </tr>--}}
{{--</table>--}}

{!! $renderHalf() !!}
</body>
</html>
