<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Despacho de materiales {{ $despacho->nro }}</title>
    <style>
        @page { margin: 16px 20px; }

        body {
            font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
            color: #172033;
            font-size: 10px;
            line-height: 1.2;
        }

        table { width: 100%; border-collapse: collapse; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .small { font-size: 8px; }
        .cell { border: 1px solid #7fa1ca; padding: 3px 5px; vertical-align: middle; }
        .mt-6 { margin-top: 6px; }
        .mt-10 { margin-top: 10px; }
        .logo { height: 58px; width: auto; display: block; margin-bottom: 3px; }
        .city { font-style: italic; font-size: 11px; color: #0f5ea8; }

        .doc-head td { vertical-align: top; }
        .doc-number-table { width: 76%; margin-left: auto; border-collapse: collapse; }
        .doc-number-table td { border: 1px solid #0f5ea8; padding: 4px 6px; font-size: 12px; }
        .doc-number-table td:first-child { background: #e8f1fb; color: #0f5ea8; }

        .title {
            text-align: center;
            font-size: 31px;
            letter-spacing: .5px;
            margin-top: 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
            text-decoration: underline;
            color: #0f5ea8;
        }
        .subtitle-meta {
            text-align: center;
            font-size: 9px;
            color: #475569;
            margin-bottom: 6px;
            border-bottom: 1px solid #c8e0f7;
            padding-bottom: 4px;
        }
        .subtitle-meta .highlight {
            color: #0f5ea8;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-table td.label {
            width: 18%;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #0f5ea8;
            background: #edf5ff;
        }

        .items-table th {
            border: 1px solid #0f5ea8;
            background: #0f5ea8;
            color: #fff;
            padding: 4px 3px;
            font-size: 9px;
            text-transform: uppercase;
        }
        .items-table td {
            border: 1px solid #7fa1ca;
            padding: 4px 4px;
            vertical-align: top;
        }
        .items-table tbody tr:nth-child(even) td { background: #f8fbff; }

        .totals-wrap {
            width: 38%;
            margin-left: auto;
            margin-top: 10px;
            margin-bottom: 8px;
        }
        .totals-wrap td {
            border: 1px solid #7fa1ca;
            padding: 5px 7px;
            font-size: 10px;
        }
        .totals-wrap td.label {
            color: #475569;
            background: #f8fbff;
        }
        .totals-wrap td.value {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }
        .totals-wrap tr.total td {
            background: #0f5ea8;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
        }

        .obs td {
            border: 1px solid #7fa1ca;
            padding: 4px 5px;
        }
        .obs-label { color: #0f5ea8; background: #edf5ff; }
        .clearfix { clear: both; }

        .signatures {
            width: 100%;
            margin-top: 42px;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 42px;
        }
        .line {
            width: 70%;
            margin: 0 auto 4px;
            border-top: 1px solid #0f5ea8;
        }
        .stamp {
            margin-top: 3px;
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
        }
        .firma-img { max-height: 42px; max-width: 130px; margin-bottom: -8px; }
        .sello-img { max-height: 44px; max-width: 120px; margin-top: 3px; }
        .firma-space { height: 42px; }
        .anulado-watermark {
            position: fixed; top: 35%; left: 5%; right: 5%;
            text-align: center; font-size: 110px; color: rgba(200, 0, 0, 0.18);
            transform: rotate(-25deg); font-weight: bold; letter-spacing: 8px;
        }
    </style>
</head>
<body>
@php
    $logoPath = public_path('img/logo-hospital.png');
    $fechaEntrega = $despacho->fecha_entrega ? \Carbon\Carbon::parse($despacho->fecha_entrega) : null;
    $fechaPedido = optional($despacho->pedido)->fecha_hora ? \Carbon\Carbon::parse($despacho->pedido->fecha_hora) : null;
    $totalItems = $despacho->detalles->sum('cantidad');
    $despachoUser = $despacho->user;
    $firmaDespachoPath = $despachoUser && $despachoUser->mostrar_firma && $despachoUser->firma ? public_path('images/'.$despachoUser->firma) : null;
    $selloDespachoPath = $despachoUser && $despachoUser->mostrar_sello && $despachoUser->sello ? public_path('images/'.$despachoUser->sello) : null;
@endphp

@if($despacho->estado === 'ANULADO')
    <div class="anulado-watermark">ANULADO</div>
@endif

<table class="doc-head">
    <tr>
        <td style="width:33%;">
            @if (file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="Logo">
            @endif
            <div class="city">Oruro - Bolivia</div>
        </td>
        <td style="width:34%;"></td>
        <td style="width:33%;">
            <table class="doc-number-table">
                <tr>
                    <td class="bold uppercase center">N&deg;</td>
                    <td class="center">{{ $despacho->nro ?? $despacho->id }}</td>
                </tr>
                <tr>
                    <td class="bold uppercase center">Pedido</td>
                    <td class="center">#{{ $despacho->pedido_id ?: '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="title">Despacho de materiales</div>
<div class="subtitle-meta">
    Entrega: <span class="highlight">{{ $fechaEntrega ? $fechaEntrega->format('d/m/Y H:i') : '-' }}</span>
    &nbsp;|&nbsp;
    Responsable: <span class="highlight">{{ optional($despacho->user)->name ?: '-' }}</span>
    &nbsp;|&nbsp;
    Estado: <span class="highlight">{{ $despacho->estado }}</span>
</div>

<table class="info-table mt-6">
    <tr>
        <td class="cell label">Solicitante</td>
        <td class="cell uppercase">{{ $despacho->solicitante ?: '-' }}</td>
        <td class="cell label">Servicio</td>
        <td class="cell uppercase">{{ $despacho->servicio ?: '-' }}</td>
    </tr>
    <tr>
        <td class="cell label">Recepcion</td>
        <td class="cell uppercase">{{ $despacho->personal_recepcion ?: '-' }}</td>
        <td class="cell label">Fecha entrega</td>
        <td class="cell">{{ $fechaEntrega ? $fechaEntrega->format('d/m/Y H:i') : '-' }}</td>
    </tr>
    <tr>
        <td class="cell label">Pedido N&deg;</td>
        <td class="cell">#{{ $despacho->pedido_id ?: '-' }}</td>
        <td class="cell label">Fecha pedido</td>
        <td class="cell">{{ $fechaPedido ? $fechaPedido->format('d/m/Y H:i') : '-' }}</td>
    </tr>
    <tr>
        <td class="cell label">Registrado por</td>
        <td class="cell uppercase">{{ optional($despacho->user)->name ?: '-' }}</td>
        <td class="cell label">Items</td>
        <td class="cell">{{ count($despacho->detalles) }}</td>
    </tr>
</table>

<table class="items-table mt-10">
    <thead>
        <tr>
            <th style="width:6%;">Item</th>
            <th style="width:10%;">Cantidad</th>
            <th style="width:10%;">Unidad</th>
            <th style="width:42%;">Descripcion</th>
            <th style="width:10%;">Lote</th>
            <th style="width:10%;">Vence</th>
            <th style="width:11%;">Precio unit.</th>
            <th style="width:11%;">Total Bs.</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($despacho->detalles as $det)
            <tr>
                <td class="center">{{ $det->item }}</td>
                <td class="right">{{ $det->cantidad }}</td>
                <td class="center uppercase">{{ $det->unidad ?: 'UND' }}</td>
                <td class="uppercase">{{ $det->descripcion }}</td>
                <td class="center small">{{ $det->lote ?: '-' }}</td>
                <td class="center small">{{ $det->fecha_vencimiento ? \Carbon\Carbon::parse($det->fecha_vencimiento)->format('d/m/y') : '-' }}</td>
                <td class="right">{{ number_format((float) $det->precio_unitario, 2, ',', '.') }}</td>
                <td class="right">{{ number_format((float) $det->total, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td class="center" colspan="8">Sin productos</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="totals-wrap">
    <tr>
        <td class="label">Unidades</td>
        <td class="value">{{ $totalItems }}</td>
    </tr>
    <tr>
        <td class="label">Items</td>
        <td class="value">{{ count($despacho->detalles) }}</td>
    </tr>
    <tr class="total">
        <td>Total</td>
        <td class="value">{{ number_format((float) $despacho->total, 2, ',', '.') }} Bs</td>
    </tr>
</table>
<div class="clearfix"></div>

<table class="obs">
    <tr>
        <td style="width:18%;" class="bold uppercase obs-label">Observaciones:</td>
        <td>{{ $despacho->observaciones ?: '-' }}</td>
    </tr>
</table>

<table class="signatures">
    <tr>
        <td>
            @if($firmaDespachoPath && file_exists($firmaDespachoPath))
                <img src="{{ $firmaDespachoPath }}" class="firma-img" alt="firma">
            @else
                <div class="firma-space"></div>
            @endif
            <div class="line"></div>
            <div class="bold uppercase">Responsable de despacho</div>
            <div class="stamp">{{ optional($despacho->user)->name ?: '-' }}</div>
            @if($selloDespachoPath && file_exists($selloDespachoPath))
                <img class="sello-img" src="{{ $selloDespachoPath }}" alt="Sello">
            @endif
        </td>
        <td>
            <div class="line"></div>
            <div class="bold uppercase">Responsable de recepcion</div>
            <div class="stamp">{{ $despacho->personal_recepcion ?: '-' }}</div>
        </td>
    </tr>
</table>

</body>
</html>
