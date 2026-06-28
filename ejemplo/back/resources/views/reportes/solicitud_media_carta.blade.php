<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados de Laboratorio</title>
    <style>
        * { box-sizing: border-box; padding: 0; margin: 0; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 10px;
        }

        .page {
            width: 100%;
            min-height: 50%; /* ocupa aprox. media hoja */
            border: 1px solid #000;
        }

        .page-break {
            page-break-after: always;
        }

        .encabezado {
            text-align: center;
            margin-bottom: 4px;
        }

        .encabezado h3 {
            margin: 0;
            font-size: 11px;
            text-transform: uppercase;
        }

        .encabezado .sub {
            font-size: 8px;
            margin-top: 2px;
        }

        .row {
            width: 100%;
            display: table;
            table-layout: fixed;
        }
        .col-4 { display: table-cell; width: 33%; vertical-align: top; }
        .col-6 { display: table-cell; width: 50%; vertical-align: top; }

        .bloque-titulo {
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        th, td {
            border: 0.5px solid #000;
            padding: 1px 2px;
        }

        th {
            font-size: 8px;
            background: #eee;
        }

        .seccion-titulo {
            font-weight: bold;
            font-size: 8px;
            margin-top: 2px;
            margin-bottom: 1px;
            text-transform: uppercase;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-left   { text-align: left; }

        /* ====== VALORES FUERA DE RANGO ====== */
        .out-of-range {
            color: #c00000;        /* rojo fuerte */
            font-weight: bold;
        }
    </style>
</head>
<body>

{{-- ========== PÁGINA 1: HEMATOLOGÍA / HEMOGRAMA ========== --}}
<div class="page page-break">
    <table>
        <tr>
            <td style="width: 20%; text-align: left;" valign="top">
                {{-- LOGO IZQUIERDO --}}
                <div style="text-align: center">
                    <img src="{{ public_path('img/logo-hospital.png') }}" style="height:50px;" alt="Logo">
                </div>
            </td>
            <td style="width: 60%; text-align: center;" valign="top">
                <div class="encabezado">
                    <h3>HOSPITAL GENERAL &quot;JUAN DE DIOS&quot; ORURO</h3>
                    <div class="sub">LABORATORIO DE ANÁLISIS CLÍNICO - MICROBIOLÓGICO</div>
                    <div class="sub">San Felipe entre 6 de Octubre y Tarija</div>
                    <div class="sub">REGISTRO CONALAB: 001 REGISTRO CODELAB: 00004</div>
                </div>
            </td>
            <td style="width: 20%; text-align: right;" valign="top">
                {{-- LOGO DERECHO --}}
                <div style="text-align: center">
                    <img src="{{ public_path('img/logo-salud.png') }}" style="height:50px;" alt="Logo">
                </div>
            </td>
        </tr>
    </table>

    <div class="bloque-titulo">HEMOGRAMA</div>

    {{-- Datos del paciente / solicitud --}}
    <table style="margin-bottom: 4px;">
        <tr>
            <td><strong>Paciente:</strong> {{ $solicitud->paciente_nombre ?? $solicitud->paciente->nombre_completo }}</td>
            <td><strong>Edad:</strong> {{ $solicitud->paciente_edad ?? $solicitud->paciente->edad }} años</td>
            <td><strong>Sexo:</strong> {{ $solicitud->paciente_genero ?? $solicitud->paciente->genero }}</td>
        </tr>
        <tr>
            <td><strong>Médico:</strong> {{ $solicitud->doctor_nombre ?? optional($solicitud->doctor)->nombre }}</td>
            <td><strong>Fecha de proceso:</strong> {{ ($solicitud->fecha_finalizacion) }}</td>
            <td>
                <strong>CODIGO:</strong> {{ $solicitud->codigo }}  {{$solicitud->tipo_atencion=='SI' ?'SUS':'EXT'}}<br>
                <strong>Registro:</strong> {{ $solicitud->codigo }}-{{ $solicitud->nro_registro }}
            </td>
        </tr>
    </table>

    <div class="row">
        {{-- COLUMNA IZQUIERDA: parámetros principales + recuento diferencial --}}
        <div class="col-6">
            @php
                $izq = $hemoItems->where('columna', 'IZQ')->groupBy('seccion');
            @endphp

            @foreach($izq as $seccion => $items)
                @if($seccion)
                    <div class="seccion-titulo">{{ $seccion }}</div>
                @endif
                <table>
                    <thead>
                    <tr>
                        <th class="text-left">Prueba</th>
                        <th class="text-center">Resultado</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-center">Rango ref.</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        @php
                            $r   = $item->areaRango;
                            $res = $resultados->firstWhere('area_rango_id', $r->id);
                            $valor = $res?->valor_final;

                            // para Hematología área 1, mostramos el final calculado
                            if ($res && $res->area_id == 1 && $res->metodo_final) {
                                $valor = $res->valor_final;
                            }

                            $unidad = $res?->unidad ?: $r->unidad;

                            $rangoTexto = '';
                            if(!is_null($r->rango_minimo) && !is_null($r->rango_maximo)){
                                $rangoTexto = $r->rango_minimo . ' - ' . $r->rango_maximo;
                            }elseif(!is_null($r->rango_minimo)){
                                $rangoTexto = '≥ ' . $r->rango_minimo;
                            }elseif(!is_null($r->rango_maximo)){
                                $rangoTexto = '≤ ' . $r->rango_maximo;
                            }

                            // ---- fuera de rango? ----
                            $esFueraRango = false;
                            if ($valor !== null && is_numeric($valor)) {
                                $v = (float) $valor;
                                if (!is_null($r->rango_minimo) && $v < $r->rango_minimo) {
                                    $esFueraRango = true;
                                }
                                if (!is_null($r->rango_maximo) && $v > $r->rango_maximo) {
                                    $esFueraRango = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="text-left">{{ $r->rango_nombre }}</td>
                            <td class="text-center {{ $esFueraRango ? 'out-of-range' : '' }}">
                                {{ $valor !== null ? $valor : '' }}
                            </td>
                            <td class="text-center">{{ $unidad }}</td>
                            <td class="text-center">{{ $rangoTexto }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>

        {{-- COLUMNA DERECHA: índices hematimétricos, grupo sanguíneo, coagulog. --}}
        <div class="col-6">
            @php
                $der = $hemoItems->where('columna', 'DER')->groupBy('seccion');
            @endphp

            @foreach($der as $seccion => $items)
                @if($seccion)
                    <div class="seccion-titulo">{{ $seccion }}</div>
                @endif
                <table>
                    <thead>
                    <tr>
                        <th class="text-left">Prueba</th>
                        <th class="text-center">Resultado</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-center">Rango ref.</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        @php
                            $r   = $item->areaRango;
                            $res = $resultados->firstWhere('area_rango_id', $r->id);
                            $valor = $res?->valor_final;
                            $unidad = $res?->unidad ?: $r->unidad;

                            $rangoTexto = '';
                            if(!is_null($r->rango_minimo) && !is_null($r->rango_maximo)){
                                $rangoTexto = $r->rango_minimo . ' - ' . $r->rango_maximo;
                            }elseif(!is_null($r->rango_minimo)){
                                $rangoTexto = '≥ ' . $r->rango_minimo;
                            }elseif(!is_null($r->rango_maximo)){
                                $rangoTexto = '≤ ' . $r->rango_maximo;
                            }

                            // ---- fuera de rango? ----
                            $esFueraRango = false;
                            if ($valor !== null && is_numeric($valor)) {
                                $v = (float) $valor;
                                if (!is_null($r->rango_minimo) && $v < $r->rango_minimo) {
                                    $esFueraRango = true;
                                }
                                if (!is_null($r->rango_maximo) && $v > $r->rango_maximo) {
                                    $esFueraRango = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="text-left">{{ $r->rango_nombre }}</td>
                            <td class="text-center {{ $esFueraRango ? 'out-of-range' : '' }}">
                                {{ $valor !== null ? $valor : '' }}
                            </td>
                            <td class="text-center">{{ $unidad }}</td>
                            <td class="text-center">{{ $rangoTexto }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>

    <div style="margin-top: 6px; font-size: 8px;">
        <strong>Observaciones:</strong> ____________________________________________
        <div style="text-align: right; margin-top: 4px;">
            Resposable: Dr/a. {{$solicitud->userAnalitica->name}}<br>
            BIOQUIMICA/O <br>
            Fecha de emisión: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</div>

{{-- ========== PÁGINA 2: QUÍMICA SANGUÍNEA / SEROLOGÍA ========== --}}
<div class="page">
    <div class="encabezado">
        <h3>HOSPITAL GENERAL &quot;JUAN DE DIOS&quot; ORURO</h3>
        <div class="sub">LABORATORIO DE ANÁLISIS CLÍNICO - MICROBIOLÓGICO</div>
    </div>

    <div class="bloque-titulo">QUÍMICA SANGUÍNEA Y SEROLOGÍA</div>

    <table style="margin-bottom: 4px;">
        <tr>
            <td><strong>Paciente:</strong> {{ $solicitud->paciente_nombre ?? $solicitud->paciente->nombre_completo }}</td>
            <td><strong>Edad:</strong> {{ $solicitud->paciente_edad ?? $solicitud->paciente->edad }} años</td>
            <td><strong>Fecha:</strong> {{ optional($solicitud->fecha_envio_analitica)->format('d/m/Y') }}</td>
        </tr>
    </table>

    @php
        $qizq = $quimicaItems->where('columna', 'IZQ')->groupBy('seccion');
        $qder = $quimicaItems->where('columna', 'DER')->groupBy('seccion');
    @endphp

    <div class="row">
        <div class="col-6">
            @foreach($qizq as $seccion => $items)
                @if($seccion)
                    <div class="seccion-titulo">{{ $seccion }}</div>
                @endif
                <table>
                    <thead>
                    <tr>
                        <th class="text-left">Analito</th>
                        <th class="text-center">Resultado</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-center">Rango ref.</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        @php
                            $r   = $item->areaRango;
                            $res = $resultados->firstWhere('area_rango_id', $r->id);
                            $valor  = $res?->valor_final;
                            $unidad = $res?->unidad ?: $r->unidad;

                            $rangoTexto = '';
                            if(!is_null($r->rango_minimo) && !is_null($r->rango_maximo)){
                                $rangoTexto = $r->rango_minimo . ' - ' . $r->rango_maximo;
                            }elseif(!is_null($r->rango_minimo)){
                                $rangoTexto = '≥ ' . $r->rango_minimo;
                            }elseif(!is_null($r->rango_maximo)){
                                $rangoTexto = '≤ ' . $r->rango_maximo;
                            }

                            // ---- fuera de rango? ----
                            $esFueraRango = false;
                            if ($valor !== null && is_numeric($valor)) {
                                $v = (float) $valor;
                                if (!is_null($r->rango_minimo) && $v < $r->rango_minimo) {
                                    $esFueraRango = true;
                                }
                                if (!is_null($r->rango_maximo) && $v > $r->rango_maximo) {
                                    $esFueraRango = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="text-left">{{ $r->rango_nombre }}</td>
                            <td class="text-center {{ $esFueraRango ? 'out-of-range' : '' }}">
                                {{ $valor !== null ? $valor : '' }}
                            </td>
                            <td class="text-center">{{ $unidad }}</td>
                            <td class="text-center">{{ $rangoTexto }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>

        <div class="col-6">
            @foreach($qder as $seccion => $items)
                @if($seccion)
                    <div class="seccion-titulo">{{ $seccion }}</div>
                @endif
                <table>
                    <thead>
                    <tr>
                        <th class="text-left">Analito</th>
                        <th class="text-center">Resultado</th>
                        <th class="text-center">Unidad</th>
                        <th class="text-center">Rango ref.</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        @php
                            $r   = $item->areaRango;
                            $res = $resultados->firstWhere('area_rango_id', $r->id);
                            $valor  = $res?->valor_final;
                            $unidad = $res?->unidad ?: $r->unidad;

                            $rangoTexto = '';
                            if(!is_null($r->rango_minimo) && !is_null($r->rango_maximo)){
                                $rangoTexto = $r->rango_minimo . ' - ' . $r->rango_maximo;
                            }elseif(!is_null($r->rango_minimo)){
                                $rangoTexto = '≥ ' . $r->rango_minimo;
                            }elseif(!is_null($r->rango_maximo)){
                                $rangoTexto = '≤ ' . $r->rango_maximo;
                            }

                            // ---- fuera de rango? ----
                            $esFueraRango = false;
                            if ($valor !== null && is_numeric($valor)) {
                                $v = (float) $valor;
                                if (!is_null($r->rango_minimo) && $v < $r->rango_minimo) {
                                    $esFueraRango = true;
                                }
                                if (!is_null($r->rango_maximo) && $v > $r->rango_maximo) {
                                    $esFueraRango = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="text-left">{{ $r->rango_nombre }}</td>
                            <td class="text-center {{ $esFueraRango ? 'out-of-range' : '' }}">
                                {{ $valor !== null ? $valor : '' }}
                            </td>
                            <td class="text-center">{{ $unidad }}</td>
                            <td class="text-center">{{ $rangoTexto }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
</div>

</body>
</html>
