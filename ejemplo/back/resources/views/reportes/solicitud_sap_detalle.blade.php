<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Solicitud SAP {{ $solicitud->nro }}</title>
    <style>
        @page { margin: 18px 22px; }

        body {
            font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
            color: #172033;
            font-size: 9px;
            line-height: 1.25;
        }

        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }
        .upper  { text-transform: uppercase; }
        .small  { font-size: 8px; }
        .tiny   { font-size: 7px; }

        /* Cabecera */
        .logo { height: 52px; width: auto; display: block; }
        .hosp-name { font-size: 9px; font-weight: bold; color: #0f5ea8; line-height: 1.2; }
        .hosp-city { font-style: italic; font-size: 8px; color: #444; }

        /* Correlativo box */
        .corr-box { border: 1px solid #0f5ea8; padding: 3px 8px; font-size: 11px; font-weight: bold; color: #0f5ea8; text-align: center; }
        .corr-label { font-size: 8px; color: #666; text-align: center; }

        /* Título */
        .doc-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #0f5ea8;
            text-decoration: underline;
            margin: 8px 0 4px;
        }

        /* Grilla de meta-datos */
        .meta-table td {
            border: 1px solid #7fa1ca;
            padding: 3px 5px;
            vertical-align: middle;
        }
        .meta-table td.lbl {
            background: #e8f1fb;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f5ea8;
            font-size: 8px;
            white-space: nowrap;
        }
        .meta-table td.val { font-size: 9px; }

        /* Tabla de ítems */
        .items-table { margin-top: 8px; }
        .items-table th {
            border: 1px solid #0f5ea8;
            background: #0f5ea8;
            color: #fff;
            padding: 4px 3px;
            font-size: 8px;
            text-transform: uppercase;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #7fa1ca;
            padding: 4px 4px;
            vertical-align: middle;
        }
        .items-table tbody tr:nth-child(even) td { background: #f4f8ff; }

        /* Total */
        .total-wrap { width: 38%; margin-left: auto; margin-top: 6px; }
        .total-wrap td { border: 1px solid #7fa1ca; padding: 4px 6px; }
        .total-wrap td.lbl { background: #f4f8ff; color: #475569; }
        .total-wrap td.val { text-align: right; font-weight: bold; }
        .total-wrap tr.grand td { background: #0f5ea8; color: #fff; font-weight: bold; font-size: 11px; }

        /* Son */
        .son-table { margin-top: 5px; }
        .son-table td {
            border: 1px solid #7fa1ca;
            padding: 3px 5px;
        }
        .son-table td.lbl { background: #e8f1fb; font-weight: bold; color: #0f5ea8; width: 14%; font-size: 8px; text-transform: uppercase; }

        /* Justificación */
        .just-table { margin-top: 5px; }
        .just-table td { border: 1px solid #7fa1ca; padding: 4px 6px; }
        .just-table td.lbl { background: #e8f1fb; font-weight: bold; color: #0f5ea8; font-size: 8px; text-transform: uppercase; width: 25%; vertical-align: top; }

        /* Firmas */
        .firma-section {
            margin-top: 8px;
            border: 1px solid #aac8e0;
            padding: 4px 6px;
        }
        .firma-title {
            background: #0f5ea8;
            color: #fff;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            padding: 2px 5px;
            text-align: center;
            margin-bottom: 4px;
        }
        .firma-grid td {
            width: 50%;
            text-align: center;
            padding-top: 8px;
            vertical-align: bottom;
        }
        .firma-line { width: 65%; border-top: 1px solid #0f5ea8; margin: 0 auto 3px; }
        .firma-role { font-size: 8px; font-weight: bold; text-transform: uppercase; color: #0f5ea8; }
        .firma-name { font-size: 8px; color: #666; margin-top: 2px; }
        .firma-img { max-height: 38px; max-width: 120px; margin-bottom: -7px; }
        .sello-img { max-height: 40px; max-width: 115px; margin-top: 3px; }
        .firma-space { height: 38px; }

        .cert-section {
            margin-top: 6px;
            border: 1px solid #aac8e0;
            padding: 4px 6px;
        }
        .cert-title {
            background: #e8f1fb;
            color: #0f5ea8;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
            padding: 2px 5px;
            text-align: center;
            margin-bottom: 4px;
            border-bottom: 1px solid #aac8e0;
        }
        .cert-grid td {
            width: 50%;
            text-align: center;
            padding-top: 14px;
            vertical-align: bottom;
        }

        .clearfix { clear: both; }
    </style>
</head>
<body>
@php
    $logoPath  = public_path('img/logo-hospital.png');
    $fecha     = $solicitud->fecha ? \Carbon\Carbon::parse($solicitud->fecha) : null;
    $createdAt = $solicitud->created_at ? \Carbon\Carbon::parse($solicitud->created_at) : null;
    $sapUser = $solicitud->user;
    $firmaSapPath = $sapUser && $sapUser->mostrar_firma && $sapUser->firma ? public_path('images/'.$sapUser->firma) : null;
    $selloSapPath = $sapUser && $sapUser->mostrar_sello && $sapUser->sello ? public_path('images/'.$sapUser->sello) : null;
@endphp

{{-- CABECERA --}}
<table>
    <tr>
        <td style="width:22%; vertical-align:middle;">
            @if(file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="Logo">
            @endif
            <div class="hosp-name">Hospital General San Juan de Dios</div>
            <div class="hosp-city">Oruro &ndash; Bolivia</div>
        </td>
        <td style="width:50%; vertical-align:middle; text-align:center;"></td>
        <td style="width:28%; vertical-align:top; text-align:right;">
            <div class="corr-label">N° CORRELATIVO</div>
            <div class="corr-box">{{ $solicitud->nro }}</div>
        </td>
    </tr>
</table>

<div class="doc-title">Solicitud de Contratación de Servicios Generales</div>

{{-- FILA: N° SOLICITUD | FECHA | HORA | N° CITE --}}
<table class="meta-table" style="margin-top:4px;">
    <tr>
        <td class="lbl" style="width:14%;">N° Solicitud</td>
        <td class="val center" style="width:10%;">{{ $solicitud->id }}</td>
        <td class="lbl" style="width:8%;">Fecha</td>
        <td class="val center" style="width:14%;">{{ $fecha ? $fecha->format('d/m/Y') : '-' }}</td>
        <td class="lbl" style="width:8%;">Hora</td>
        <td class="val center" style="width:12%;">{{ $createdAt ? $createdAt->format('H:i') : '-' }}</td>
        <td class="lbl" style="width:18%;">N° CITE Solicitud</td>
        <td class="val center" style="width:16%;">{{ $solicitud->nro_cite ?: '-' }}</td>
    </tr>
</table>

{{-- UNIDAD SOLICITANTE --}}
<table class="meta-table" style="margin-top:1px;">
    <tr>
        <td class="lbl" style="width:20%;">Unidad Solicitante</td>
        <td class="val upper" colspan="3">{{ $solicitud->unidad_solicitante ?: '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Apertura Programática / SISÍN</td>
        <td class="val bold" colspan="3">{{ $solicitud->apertura_programatica ?: '-' }}</td>
    </tr>
    <tr>
        <td class="lbl">Registrado por</td>
        <td class="val">{{ optional($solicitud->user)->name ?: '-' }}</td>
        <td class="lbl" style="width:12%;">Estado</td>
        <td class="val bold">{{ $solicitud->estado }}</td>
    </tr>
</table>

{{-- TABLA DE ÍTEMS --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width:6%;">Ítem</th>
            <th style="width:11%;">Partida<br>Pptaria</th>
            <th style="width:10%;">Unidad de<br>Medida</th>
            <th style="width:9%;">Cantidad</th>
            <th style="width:38%;">Descripción del (de los) Servicio(s) a Adquirir</th>
            <th style="width:13%;">Precio Ref.<br>Unitario</th>
            <th style="width:13%;">Precio Ref.<br>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($solicitud->detalles as $idx => $det)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td class="center small">{{ $det->part_presup ?: '-' }}</td>
                <td class="center upper">{{ $det->unidad ?: 'UND' }}</td>
                <td class="center">{{ number_format((float)$det->cantidad, 0, '.', '') }}</td>
                <td class="upper">{{ $det->descripcion }}</td>
                <td class="right">{{ number_format((float)$det->precio_unitario, 2, ',', '.') }}</td>
                <td class="right bold">{{ number_format((float)$det->total, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td class="center" colspan="7">Sin ítems</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- TOTAL --}}
<table class="total-wrap">
    <tr>
        <td class="lbl">Ítems</td>
        <td class="val">{{ $solicitud->detalles->count() }}</td>
    </tr>
    <tr class="grand">
        <td>TOTAL</td>
        <td class="val right">{{ number_format((float)$solicitud->total, 2, ',', '.') }} Bs</td>
    </tr>
</table>
<div class="clearfix"></div>

{{-- SON --}}
<table class="son-table">
    <tr>
        <td class="lbl">Son:</td>
        <td class="upper bold">{{ number_format((float)$solicitud->total, 2, ',', '.') }} bolivianos</td>
    </tr>
</table>

{{-- JUSTIFICACIÓN --}}
@if($solicitud->justificacion)
<table class="just-table">
    <tr>
        <td class="lbl">Justificación del Requerimiento:</td>
        <td>{{ $solicitud->justificacion }}</td>
    </tr>
</table>
@endif

{{-- FIRMAS DE LA UNIDAD SOLICITANTE --}}
<div class="firma-section" style="margin-top:8px;">
    <div class="firma-title">Firmas de la Unidad Solicitante</div>
    <table class="firma-grid">
        <tr>
            <td>
                @if($firmaSapPath && file_exists($firmaSapPath))
                    <img class="firma-img" src="{{ $firmaSapPath }}" alt="Firma">
                @else
                    <div class="firma-space"></div>
                @endif
                <div class="firma-line"></div>
                <div class="firma-role">Elaborado por</div>
                <div class="firma-name">{{ optional($solicitud->user)->name ?: '-' }}</div>
                @if($selloSapPath && file_exists($selloSapPath))
                    <img class="sello-img" src="{{ $selloSapPath }}" alt="Sello">
                @endif
            </td>
            <td>
                <div class="firma-line"></div>
                <div class="firma-role">Aprobado por</div>
                <div class="firma-name">(Jefe de Unidad / Director / Secretario Departamental)</div>
            </td>
        </tr>
    </table>
</div>

{{-- CONTROL DEL SERVICIO / CERTIFICACIÓN PRESUPUESTARIA --}}
<div class="cert-section">
    <div class="cert-title">Control del Servicio(s)</div>
    <table class="cert-grid">
        <tr>
            <td>
                <div class="firma-line"></div>
                <div class="firma-role">Sello y Firma</div>
                <div class="firma-name">(Almacén Central)</div>
            </td>
            <td>
                <div class="firma-line"></div>
                <div class="firma-role">Certificación Presupuestaria</div>
                <div class="firma-name">(Técnico y Responsable de Presupuestos)</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
