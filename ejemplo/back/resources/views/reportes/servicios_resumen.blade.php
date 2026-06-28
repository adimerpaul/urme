<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
</style>
</head>
<body style="padding: 15px;">

<p style="font-size:13px; font-weight:700; margin-bottom:2px;">
    Reporte de Solicitudes por Servicios / Prestaciones
</p>
<p style="font-size:8px; color:#555; margin-bottom:6px;">
    Rango: <b>{{ $dateFrom }}</b> &mdash; <b>{{ $dateTo }}</b>
    &nbsp;|&nbsp; Total: {{ $totalCount }} solicitudes
    &nbsp;|&nbsp; Embarazadas: {{ $embarazadas }}
    &nbsp;|&nbsp; Total Bs: <b>{{ number_format($totalMonto, 2) }}</b>
</p>

<table style="width:100%; border-collapse:collapse; page-break-inside:auto;">
<thead>
<tr style="background:#263238; color:#fff; font-size:8px;">
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">#</th>
    <th style="padding:3px 4px; border:1px solid #455a64;">Código</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">Fecha</th>
    <th style="padding:3px 4px; border:1px solid #455a64;">Paciente</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">CI</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">Edad</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">Gén.</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">Emb.</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:center;">Cama</th>
    <th style="padding:3px 4px; border:1px solid #455a64;">Prestaciones</th>
    <th style="padding:3px 4px; border:1px solid #455a64;">Servicios</th>
    <th style="padding:3px 4px; border:1px solid #455a64; text-align:right;">Total Bs</th>
    <th style="padding:3px 4px; border:1px solid #455a64;">Estado</th>
</tr>
</thead>
<tbody>
@foreach($rows as $i => $r)
@php
    $bg  = $i % 2 === 0 ? '#ffffff' : '#f5f5f5';
    $gc  = $r->paciente_genero === 'M' ? '#1565C0' : '#AD1457';
@endphp
<tr style="background:{{ $bg }}; page-break-inside:avoid;">
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center;">{{ $i + 1 }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc;">{{ $r->codigo_solicitud ?? $r->id }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center;">{{ $r->fecha_solicitud }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc;">{{ $r->paciente_nombre }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center;">{{ $r->paciente_ci }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center;">{{ $r->paciente_edad }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center; color:{{ $gc }}; font-weight:600;">{{ $r->paciente_genero ?: '-' }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center;">{{ $r->paciente_embarazo ? 'Si' : '' }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:center;">{{ $r->cama ?: '-' }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; font-size:7px;">{{ $r->areas_nombres ?: '-' }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; font-size:7px;">{{ $r->servicios_nombres ?: '-' }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc; text-align:right;">{{ number_format((float)$r->total_monto, 2) }}</td>
    <td style="padding:2px 4px; border:1px solid #ccc;">{{ $r->estado }}</td>
</tr>
@endforeach
</tbody>
<tfoot>
<tr style="background:#263238; color:#fff; font-weight:700;">
    <td colspan="11" style="padding:3px 4px; border:1px solid #455a64; text-align:right;">TOTAL</td>
    <td style="padding:3px 4px; border:1px solid #455a64; text-align:right;">{{ number_format($totalMonto, 2) }}</td>
    <td style="padding:3px 4px; border:1px solid #455a64;"></td>
</tr>
</tfoot>
</table>

</body>
</html>
