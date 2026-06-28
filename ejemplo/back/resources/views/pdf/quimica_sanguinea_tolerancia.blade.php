<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        *{ box-sizing:border-box; }
        body{ font-family: DejaVu Sans, sans-serif; font-size: 8px; color:#111; margin:0; }
        .center{ text-align:center; }
        .bold{ font-weight:700; }
        .tbl{ width:100%; border-collapse:collapse; }
        .tbl th,.tbl td{ border:1px solid #111; padding:2px 4px; }
        .tbl th{ background:#f3f3f3; }
        .mt-8{ margin-top:8px; }
        .title{ font-size:15px; font-weight:700; margin:6px 0 8px; }
        .chart-wrap{ text-align:center; }
        .chart-img{ width:92%; height:auto; }
        .obs-label{ font-size:10px; font-weight:700; margin-bottom:3px; }
        .obs-box{ border:1px solid #111; min-height:42px; padding:6px; font-size:9px; }
    </style>
</head>

<body>
<div style="margin-top:-30px;">
    {!! view('components.headerSinCabeceraPequeno', ['solicitud' => $solicitud, 'fecha_solicitud'=>$solicitud->created_at])->render() !!}
</div>

<div class="center title">CURVA DE TOLERANCIA A LA GLUCOSA</div>

{{-- TABLA --}}
<table class="tbl mt-8">
    <thead>
    <tr>
        <th style="width:35%;">TOMA DE MUESTRA</th>
        <th style="width:35%;">VALORES (mg/dl)</th>
        <th style="width:30%;">HORA</th>
    </tr>
    </thead>
    <tbody>
    @forelse($series as $row)
        <tr>
            <td class="center">{{ $row['toma'] }}</td>
            <td class="center">{{ number_format($row['valor'], 1, '.', '') }}</td>
            <td class="center">{{ $row['hora'] ?? '' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="3" class="center">Sin datos de tolerancia.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- GRÁFICA --}}
@if(!empty($chartBase64))
    <div class="chart-wrap mt-8">
        <img class="chart-img" src="data:image/png;base64,{{ $chartBase64 }}" alt="Curva de tolerancia">
    </div>
@endif

<div class="mt-8">
    <div class="obs-label">OBSERVACIONES</div>
    <div class="obs-box">{!! nl2br(e($quimica->observaciones ?? '')) !!}</div>
</div>

</body>
</html>
