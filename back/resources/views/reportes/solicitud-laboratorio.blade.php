<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 22px 32px 26px; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 8.5px; }
        .header, .patient, .results { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 3px; border-bottom: 2px solid #2d82b7; }
        .header td { vertical-align: middle; padding-bottom: 5px; }
        .laboratory-logo { width: 205px; height: auto; display: block; }
        .document-code { color: #155ca8; font-size: 11px; }
        .meta { color: #4b5563; font-size: 8px; line-height: 1.45; }
        .title { color: #164e7a; font-size: 12px; font-weight: bold; text-align: center; margin: 5px 0 6px; letter-spacing: .3px; }
        .patient { margin-bottom: 7px; }
        .patient td { border: 1px solid #aeb9c5; padding: 3px 5px; }
        .label { font-weight: bold; color: #374151; }
        .results th { border-top: 1.5px solid #2868a8; border-bottom: 1.5px solid #2868a8; color: #1f4f7d; padding: 4px; text-align: left; font-size: 7.5px; }
        .results td { border-bottom: 1px solid #e5e7eb; padding: 3.5px 4px; vertical-align: top; }
        .results tr { page-break-inside: avoid; }
        .section td { background: #edf4fb; color: #1f4f7d; font-size: 9px; font-weight: bold; padding: 4px; border-bottom: 1px solid #9dbbd8; }
        .result { font-weight: bold; }
        .footer { margin-top: 10px; border-top: 1px solid #c2cad3; padding-top: 5px; color: #6b7280; font-size: 7.5px; }
        .signature { margin-top: 24px; width: 230px; border-top: 1px solid #374151; text-align: center; padding-top: 3px; font-size: 8px; }
        .signature-name { margin-top: 2px; font-weight: bold; color: #164e7a; font-size: 8.5px; }
        .verification { position: fixed; right: 0; bottom: 0; width: 142px; color: #4b5563; font-size: 6.5px; }
        .verification table { width: 100%; border-collapse: collapse; text-align: center; }
        .verification td { padding: 0; }
        .verification img { width: 82px; height: 82px; }
        .verification-code { padding-top: 2px !important; white-space: nowrap; }
    </style>
</head>
<body>
<table class="header"><tr>
    <td style="width:42%">
        <img class="laboratory-logo" src="{{ public_path('images/logo-laboratorio-urme.jpg') }}" alt="Laboratorio de Diagnóstico Clínico URME">
    </td>
    <td class="meta" style="width:58%;text-align:right">
        <strong class="document-code">{{ $solicitude->codigo_solicitud }}</strong><br>
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
    @foreach($solicitude->laboratorioItems->filter(fn ($item) => $item->resultados->where('visible', true)->isNotEmpty()) as $item)
        <tr class="section"><td colspan="5">{{ $item->producto_nombre }}</td></tr>
        @foreach($item->resultados->where('visible', true) as $resultado)
            <tr>
                <td>{{ $resultado->nombre }}</td>
                <td class="result">{{ filled($resultado->valor) ? $resultado->valor : '-' }} {{ $resultado->unidad }}</td>
                <td>{{ $resultado->rango_referencia ?: '-' }}</td>
                <td>{{ $resultado->metodo ?: '-' }}</td>
                <td>{{ $resultado->muestra ?: '-' }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
@if($solicitude->observaciones)
    <div style="margin-top:12px"><span class="label">OBSERVACIONES:</span> {{ $solicitude->observaciones }}</div>
@endif
<div class="signature">
    <div>RESPONSABLE DE LABORATORIO</div>
    <div class="signature-name">{{ $impresoPor->name }}</div>
</div>
<div class="footer">
    Este informe corresponde a la solicitud {{ $solicitude->codigo_solicitud }}.
    Los resultados deben ser interpretados por un profesional de salud.
</div>
<div class="verification">
    <table>
        <tr><td><img src="{{ $qrDataUri }}" alt="QR de verificación"></td></tr>
        <tr><td class="verification-code">VERIFICAR AUTENTICIDAD<br>{{ $solicitude->codigo_verificacion }}</td></tr>
    </table>
</div>
</body>
</html>
