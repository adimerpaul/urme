<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: legal landscape; margin: 12mm 12mm 12mm 12mm; }
        * { box-sizing: border-box; }

        body {
            margin: 0; padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111;
            line-height: 1.3;
        }

        /* ── CABECERA ─────────────────────────────────────────────── */
        .header-outer {
            border: 2px solid #1a5276;
            border-radius: 3px;
            margin-bottom: 7px;
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            width: 65%;
            padding: 5px 8px;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            width: 35%;
            padding: 5px 8px;
            vertical-align: middle;
            text-align: right;
            border-left: 1px solid #1a5276;
        }
        .lab-name {
            font-size: 12px;
            font-weight: 700;
            color: #1a5276;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .doc-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .doc-sub {
            font-size: 7.5px;
            color: #555;
            margin-top: 1px;
        }
        .meta-label { font-weight: 700; color: #1a5276; }
        .meta-val   { font-size: 8.5px; }

        /* ── SECCIÓN ÁREA ─────────────────────────────────────────── */
        .area-block { margin-bottom: 8px; page-break-inside: avoid; }
        .area-header {
            background: #1a5276;
            color: #fff;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 2px 6px;
        }

        /* ── TABLA ────────────────────────────────────────────────── */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead th {
            background: #d6eaf8;
            border: 1px solid #7fb3d3;
            padding: 3px 4px;
            font-size: 7.5px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }
        tbody td {
            border: 1px solid #aed6f1;
            padding: 3px 4px;
            font-size: 8px;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) td { background: #f4f9fd; }

        .col-nro   { width:  4%; text-align: center; }
        .col-id    { width:  7%; text-align: center; font-weight: 700; color: #1a5276; }
        .col-reg   { width: 10%; text-align: center; }
        .col-pac   { width: 27%; }
        .col-anal  { width: 32%; }
        .col-firma { width: 20%; }

        .firma-box {
            border-bottom: 1px solid #555;
            height: 18px;
            margin: 0 6px;
        }

        /* ── PIE ──────────────────────────────────────────────────── */
        .footer {
            margin-top: 10px;
            border-top: 1.5px solid #1a5276;
            padding-top: 6px;
            display: table;
            width: 100%;
        }
        .footer-col {
            display: table-cell;
            vertical-align: bottom;
            padding: 0 6px;
            font-size: 8px;
        }
        .sig-line {
            border-bottom: 1px solid #333;
            display: inline-block;
            min-width: 130px;
            margin-left: 4px;
        }
        .footer-label { font-weight: 700; }
        .center { text-align: center; }
        .small  { font-size: 7px; color: #777; }
    </style>
</head>
<body>

@php
    $nro      = 1;
    $ahora    = \Carbon\Carbon::now()->format('d/m/Y H:i');
    $fechaFmt = \Carbon\Carbon::parse($fecha)->format('d/m/Y');
    $total    = collect($grupos)->sum(fn($g) => count($g['solicitudes']));
@endphp

{{-- ══════════════ CABECERA ══════════════ --}}
<div class="header-outer">
    <div class="header-left">
        <div class="lab-name">Laboratorio Clínico SIL</div>
        <div class="doc-title">Registro de Entrega de Resultados</div>
        <div class="doc-sub">Constancia oficial de entrega de análisis clínicos al paciente o responsable</div>
    </div>
    <div class="header-right">
        <div class="meta-val"><span class="meta-label">Fecha:</span> {{ $fechaFmt }}</div>
        @if(!empty($areaNombre))
            <div class="meta-val"><span class="meta-label">Área:</span> {{ $areaNombre }}</div>
        @endif
        <div class="meta-val"><span class="meta-label">Total análisis:</span> {{ $total }}</div>
        <div class="small">Generado: {{ $ahora }}</div>
    </div>
</div>

{{-- ══════════════ GRUPOS POR ÁREA ══════════════ --}}
@foreach($grupos as $grupo)
<div class="area-block">
    <div class="area-header">Área: {{ $grupo['area_nombre'] }}</div>
    <table>
        <thead>
            <tr>
                <th class="col-nro">Nro</th>
                <th class="col-id">ID</th>
                <th class="col-reg">Nro<br>Registro</th>
                <th class="col-pac">Nombre del Paciente</th>
                <th class="col-anal">Análisis / Prestaciones</th>
                <th class="col-firma" style="text-align:center;">Firma del Receptor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grupo['solicitudes'] as $sol)
            <tr>
                <td class="col-nro">{{ $nro++ }}</td>
                <td class="col-id">{{ $sol['analisis_id'] ?? $sol['solicitud_id'] }}</td>
                <td class="col-reg">{{ $sol['nro_registro'] ?? '—' }}</td>
                <td class="col-pac">{{ $sol['paciente_nombre'] }}</td>
                <td class="col-anal">{{ collect($sol['servicios'])->pluck('nombre')->implode(', ') }}</td>
                <td class="col-firma"><div class="firma-box"></div></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endforeach

{{-- ══════════════ PIE / FIRMAS RESPONSABLE ══════════════ --}}
<div class="footer">
    <div class="footer-col" style="width:42%;">
        <span class="footer-label">Responsable de entrega:</span>
        <span class="sig-line">&nbsp;</span>
    </div>
    <div class="footer-col" style="width:22%;">
        <span class="footer-label">Fecha y hora:</span>
        <span class="sig-line" style="min-width:80px;">&nbsp;</span>
    </div>
    <div class="footer-col" style="width:36%; text-align:right;">
        <span class="footer-label">Firma y sello:</span>
        <span class="sig-line">&nbsp;</span>
    </div>
</div>
<div class="center small" style="margin-top:5px;">
    Sistema de Información de Laboratorio (SIL) — Documento generado el {{ $ahora }}
</div>

</body>
</html>
