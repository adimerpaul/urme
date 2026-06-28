<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
</style>
</head>
<body style="padding:14px;">

<p style="font-size:14px; font-weight:700; color:#1B5E20; margin-bottom:3px;">
    Reporte de Material Despachado por Unidad
</p>
<p style="font-size:8.5px; color:#444; margin-bottom:10px;">
    Unidad: <b>{{ $unidad }}</b>
    &nbsp;|&nbsp; Rango: <b>{{ $dateFrom }}</b> &mdash; <b>{{ $dateTo }}</b>
    &nbsp;|&nbsp; Items: <b>{{ count($rows) }}</b>
    &nbsp;|&nbsp; Cantidad total: <b>{{ $totalCantidad }}</b>
    &nbsp;|&nbsp; Total Bs: <b>{{ number_format($totalMonto, 2) }}</b>
</p>

<table style="width:100%; border-collapse:collapse;">
<thead>
<tr style="background:#2E7D32; color:#fff; font-size:8.5px;">
    <th style="padding:4px 5px; border:1px solid #388E3C; width:40px; text-align:center;">Img</th>
    <th style="padding:4px 5px; border:1px solid #388E3C;">Producto</th>
    <th style="padding:4px 5px; border:1px solid #388E3C; text-align:center; width:70px;">Unidad</th>
    <th style="padding:4px 5px; border:1px solid #388E3C; text-align:right; width:60px;">Cantidad</th>
    <th style="padding:4px 5px; border:1px solid #388E3C; text-align:right; width:80px;">P. Unit. Bs</th>
    <th style="padding:4px 5px; border:1px solid #388E3C; text-align:right; width:80px;">Total Bs</th>
    <th style="padding:4px 5px; border:1px solid #388E3C;">Personas que retiraron</th>
</tr>
</thead>
<tbody>
@foreach($rows as $i => $r)
@php $bg = $i % 2 === 0 ? '#ffffff' : '#F1F8E9'; @endphp
<tr style="background:{{ $bg }}; page-break-inside:avoid;">
    <td style="padding:3px 4px; border:1px solid #ccc; text-align:center;">
        @php
            $imgPath = $r->imagen ? public_path('images/productos/' . $r->imagen) : null;
            $hasImg  = $imgPath && file_exists($imgPath);
        @endphp
        @if($hasImg)
            <img src="{{ $imgPath }}" style="width:32px; height:32px; object-fit:contain;" />
        @else
            <span style="font-size:7px; color:#aaa;">Sin img</span>
        @endif
    </td>
    <td style="padding:3px 4px; border:1px solid #ccc; font-size:8.5px;">{{ $r->item_nombre }}</td>
    <td style="padding:3px 4px; border:1px solid #ccc; text-align:center;">{{ $r->unidad_medida }}</td>
    <td style="padding:3px 4px; border:1px solid #ccc; text-align:right; font-weight:700;">{{ number_format((int)$r->cantidad_total) }}</td>
    <td style="padding:3px 4px; border:1px solid #ccc; text-align:right;">{{ number_format((float)$r->precio_promedio, 2) }}</td>
    <td style="padding:3px 4px; border:1px solid #ccc; text-align:right; font-weight:600; color:#1B5E20;">{{ number_format((float)$r->total_monto, 2) }}</td>
    <td style="padding:3px 4px; border:1px solid #ccc; font-size:7.5px; color:#555;">{{ $r->personas_recepcion ?: '—' }}</td>
</tr>
@endforeach
</tbody>
<tfoot>
<tr style="background:#1B5E20; color:#fff; font-weight:700;">
    <td colspan="3" style="padding:4px 5px; border:1px solid #388E3C; text-align:right;">TOTAL</td>
    <td style="padding:4px 5px; border:1px solid #388E3C; text-align:right;">{{ number_format($totalCantidad) }}</td>
    <td style="padding:4px 5px; border:1px solid #388E3C;"></td>
    <td style="padding:4px 5px; border:1px solid #388E3C; text-align:right;">{{ number_format($totalMonto, 2) }}</td>
    <td style="padding:4px 5px; border:1px solid #388E3C;"></td>
</tr>
</tfoot>
</table>

</body>
</html>
