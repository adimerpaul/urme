<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 34px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9px; }
        .header, .patient, .results { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 12px; }
        .brand { color: #155ca8; font-size: 22px; font-weight: bold; letter-spacing: -1px; }
        .brand-sub { color: #155ca8; font-size: 9px; }
        .title { font-size: 13px; font-weight: bold; text-align: center; margin: 8px 0 10px; }
        .patient { margin-bottom: 12px; }
        .patient td { border: 1px solid #9ca3af; padding: 4px 6px; }
        .label { font-weight: bold; color: #374151; }
        .results th { border-top: 1.5px solid #2868a8; border-bottom: 1.5px solid #2868a8; color: #1f4f7d; padding: 5px 4px; text-align: left; font-size: 8px; }
        .results td { border-bottom: 1px solid #e5e7eb; padding: 5px 4px; vertical-align: top; }
        .results tr { page-break-inside: avoid; }
        .section td { background: #edf4fb; color: #1f4f7d; font-size: 10px; font-weight: bold; padding: 6px 4px; border-bottom: 1px solid #9dbbd8; }
        .result { font-weight: bold; }
        .footer { margin-top: 18px; border-top: 1px solid #9ca3af; padding-top: 7px; color: #6b7280; font-size: 8px; }
        .signature { margin-top: 38px; width: 230px; border-top: 1px solid #374151; text-align: center; padding-top: 4px; }
    </style>
</head>
<body>
<table class="header"><tr>
    <td style="width:45%">
        <div class="brand">CLÍNICA URME</div>
        <div class="brand-sub">LABORATORIO DE ANÁLISIS CLÍNICO</div>
    </td>
    <td style="width:55%;text-align:right">
        <strong>{{ $solicitude->codigo_solicitud }}</strong><br>
        Fecha de solicitud: {{ $solicitude->fecha_solicitud->format('d/m/Y') }} {{ substr($solicitude->hora_solicitud, 0, 5) }}<br>
        Fecha de impresión: {{ now()->format('d/m/Y H:i') }}
    </td>
</tr></table>
<div class="title">INFORME DE LABORATORIO CLÍNICO</div>
<table class="patient">
    <tr>
        <td colspan="2"><span class="label">PACIENTE:</span> {{ $solicitude->paciente->nombre_completo }}</td>
        <td><span class="label">CI:</span> {{ $solicitude->paciente->ci ?: '-' }}</td>
    </tr>
    <tr>
        <td><span class="label">SEXO:</span> {{ $solicitude->paciente->sexo === 'F' ? 'FEMENINO' : ($solicitude->paciente->sexo === 'M' ? 'MASCULINO' : '-') }}</td>
        <td colspan="2"><span class="label">MÉDICO:</span> {{ $solicitude->doctor?->nombre ?: 'NO ASIGNADO' }}</td>
    </tr>
    <tr><td colspan="3"><span class="label">DIAGNÓSTICO:</span> {{ $solicitude->diagnostico_clinico ?: '-' }}</td></tr>
</table>
<table class="results">
    <thead><tr>
        <th style="width:25%">ANÁLISIS</th>
        <th style="width:15%">RESULTADO</th>
        <th style="width:26%">VALORES DE REFERENCIA</th>
        <th style="width:18%">MÉTODO</th>
        <th style="width:16%">MUESTRA</th>
    </tr></thead>
    <tbody>
    @foreach($solicitude->laboratorioItems as $item)
        <tr class="section"><td colspan="5">{{ $item->producto_nombre }}</td></tr>
        @foreach($item->resultados->where('visible', true) as $resultado)
            <tr>
                <td>{{ $resultado->nombre }}</td>
                <td class="result">{{ filled($resultado->valor) ? $resultado->valor : '-' }} {{ $resultado->unidad }}</td>
                <td>{{ $resultado->rango_referencia ?: '-' }}</td>
                <td>-</td>
                <td>-</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
@if($solicitude->observaciones)
    <div style="margin-top:12px"><span class="label">OBSERVACIONES:</span> {{ $solicitude->observaciones }}</div>
@endif
<div class="signature">RESPONSABLE DE LABORATORIO</div>
<div class="footer">
    Este informe corresponde a la solicitud {{ $solicitude->codigo_solicitud }}.
    Los resultados deben ser interpretados por un profesional de salud.
</div>
</body>
</html>
