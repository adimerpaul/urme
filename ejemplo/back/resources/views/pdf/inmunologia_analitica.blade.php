<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0; padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            line-height: 1.08;
            color: #111;
        }

        .muted  { color: #666; }
        .bold   { font-weight: 700; }
        .center { text-align: center; }
        .small  { font-size: 6.6px; }

        .no-border { border-collapse: collapse; width: 100%; }
        .no-border td { border: none; padding: 0; }

        .tbl {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .tbl th, .tbl td {
            border: 1px solid #111;
            padding: 1.5px 3px;
            vertical-align: middle;
        }
        .tbl th {
            background: #f7f7f7;
            font-size: 9px;
        }
        .tbl td { font-size: 9px; }

        .w-analito { width: 40%; }
        .w-res     { width: 16%; }
        .w-unid    { width: 14%; }
        .w-rango   { width: 30%; }
        .out-range { color: #c10015; font-weight: 700; }

        .section-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .02em;
            padding: 2px 3px;
            margin-top: 6px;
        }

        .hr { border-top: 1.5px solid #111; margin: 3px 0; }
    </style>
</head>
<body>
@php
    function inmuno_out_of_range($valor, $min, $max) {
        if ($valor === null || $valor === '') return false;
        $num = floatval($valor);
        if ($min !== null && $num < floatval($min)) return true;
        if ($max !== null && $num > floatval($max)) return true;
        return false;
    }

    function inmuno_rango_texto($rango) {
        if ($rango->interpretacion) return $rango->interpretacion;
        if ($rango->rango_minimo !== null && $rango->rango_maximo !== null) {
            return $rango->rango_minimo . ' - ' . $rango->rango_maximo;
        }
        if ($rango->rango_minimo !== null) return '≥ ' . $rango->rango_minimo;
        if ($rango->rango_maximo !== null) return '≤ ' . $rango->rango_maximo;
        return '';
    }

    $realizadoPor = collect($prestaciones)->map(fn($p) => $p->realizado_por)->filter()->first();
@endphp

<table>
    <tr>
        <td style="vertical-align:top; padding: 0 4px;">

            <div style="margin-top:-30px;">
                {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud' => now()->format('d/m/Y H:i')])->render() !!}
            </div>

            <div class="center bold" style="font-size:12px; margin: 5px 0 2px;">INMUNOLOGÍA</div>

            @foreach($prestaciones as $prest)
                <div class="section-title">{{ $prest->nombre }}
                    @if($prest->metodo) <span class="muted" style="font-weight:400;">({{ $prest->metodo }})</span>@endif
                    @if($prest->subarea) <span class="muted" style="font-weight:400;">— {{ $prest->subarea }}</span>@endif
                </div>

                <table class="tbl">
                    <thead>
                        <tr>
                            <th class="w-analito">Analito / Condición</th>
                            <th class="w-res center">Resultado</th>
                            <th class="w-unid center">Unidad</th>
                            <th class="w-rango">Rango de referencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prest->rangos as $rango)
                            @php $outRange = inmuno_out_of_range($rango->valor_final, $rango->rango_minimo, $rango->rango_maximo); @endphp
                            <tr>
                                <td>{{ $rango->rango_nombre }}</td>
                                <td class="center {{ $outRange ? 'out-range' : '' }}">{{ $rango->valor_final ?? '' }}</td>
                                <td class="center">{{ $rango->unidad ?? '' }}</td>
                                <td>{{ inmuno_rango_texto($rango) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach

            {{-- FIRMAS + QR --}}
            <table class="no-border" style="margin-top: 8px;">
                <tr>
                    <td class="center" style="width:33%">
                        _____________________<br><span class="small muted">Firma</span>
                    </td>
                    <td class="center" style="width:33%">
                        _____________________<br>
                        <span class="small muted">
                            {{ $realizadoPor ?: '---' }}<br>
                            Bioquímico(a)
                        </span>
                    </td>
                    <td class="center" style="width:34%">
                        @if(!empty($qrSvgBase64))
                            <img
                                src="data:image/svg+xml;base64,{{ $qrSvgBase64 }}"
                                style="width:45px; height:45px;"
                                alt="QR"
                            >
                        @endif
                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>
</body>
</html>
