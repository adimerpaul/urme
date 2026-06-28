<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pedido interno #{{ $pedido->id }}</title>
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
        .tiny { font-size: 7px; }
        .cell { border: 1px solid #7fa1ca; padding: 3px 5px; vertical-align: middle; }

        .top-brand {
            text-align: center;
            font-size: 12px;
            text-transform: uppercase;
            color: #0f5ea8;
            line-height: 1.25;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .top-brand .city {
            color: #475569;
            font-size: 11px;
            text-transform: none;
            font-weight: normal;
        }

        .pedido-nro {
            width: 34%;
            margin-left: auto;
            margin-bottom: 8px;
        }
        .pedido-nro td {
            border: 1px solid #0f5ea8;
            background: #edf5ff;
            color: #0f5ea8;
            padding: 6px 8px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .pedido-nro td.value {
            width: 38%;
            text-align: center;
            color: #172033;
            background: #fff;
        }

        .title {
            text-align: center;
            font-size: 34px;
            letter-spacing: .5px;
            margin: 4px 0 6px;
            text-transform: uppercase;
            text-decoration: underline;
            color: #0f5ea8;
        }

        .subtitle-meta {
            text-align: center;
            font-size: 9px;
            color: #475569;
            margin-bottom: 7px;
            border-bottom: 1px solid #c8e0f7;
            padding-bottom: 4px;
        }
        .subtitle-meta .highlight {
            color: #0f5ea8;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info td.label {
            width: 19%;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
            color: #0f5ea8;
            background: #edf5ff;
        }

        .items th {
            border: 1px solid #0f5ea8;
            background: #0f5ea8;
            color: #fff;
            padding: 4px 3px;
            font-size: 9px;
            text-transform: uppercase;
        }
        .items td {
            border: 1px solid #7fa1ca;
            padding: 4px 4px;
            vertical-align: top;
        }
        .items tbody tr:nth-child(even) td { background: #f8fbff; }

        .total-row td {
            border: 1px solid #7fa1ca;
            padding: 5px 6px;
            font-size: 12px;
        }
        .total-row td.label {
            width: 90%;
            text-align: right;
            color: #0f5ea8;
            font-weight: bold;
            text-transform: uppercase;
        }
        .total-row td.value {
            background: #0f5ea8;
            color: #fff;
            text-align: right;
            font-weight: bold;
        }
        .total-row td.son {
            background: #edf5ff;
            color: #172033;
            text-align: left;
            font-size: 10px;
        }

        .print-date {
            margin-top: 6px;
            text-align: right;
            color: #475569;
            font-size: 9px;
        }

        .signatures {
            width: 100%;
            margin-top: 18px;
        }
        .signatures td {
            width: 25%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 12px;
        }
        .line {
            width: 82%;
            margin: 0 auto 4px;
            border-top: 1px solid #0f5ea8;
        }
        .sign-title {
            color: #0f5ea8;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .sign-name {
            color: #334155;
            font-size: 8px;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .firma-img { max-height: 42px; max-width: 130px; margin-bottom: -8px; }
        .sello-img { max-height: 44px; max-width: 120px; margin-top: 3px; }
        .firma-space { height: 42px; }
    </style>
</head>
<body>
@php
    $fechaPedido = $pedido->fecha_hora ? \Carbon\Carbon::parse($pedido->fecha_hora) : null;
    $solicitante = $pedido->nombre_usuario ?: '-';
    $servicioSolicitante = $pedido->unidad?->nombre ?: '-';
    $respPedido = optional($pedido->user)->name ?: '-';
    $pedidoUser = $pedido->user;
    $firmaPedidoPath = $pedidoUser && $pedidoUser->mostrar_firma && $pedidoUser->firma ? public_path('images/'.$pedidoUser->firma) : null;
    $selloPedidoPath = $pedidoUser && $pedidoUser->mostrar_sello && $pedidoUser->sello ? public_path('images/'.$pedidoUser->sello) : null;
@endphp

<table>
    <tr>
        <td style="width:60%;">
            <div class="top-brand">
                Hospital General San Juan de Dios<br>
                Almacen Central<br>
                <span class="city">Oruro-Bolivia</span>
            </div>
        </td>
        <td style="width:40%; vertical-align: top;">
            <table class="pedido-nro">
                <tr>
                    <td>Pedido N&deg;</td>
                    <td class="value">{{ $pedido->id }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="title">Pedido interno de materiales</div>
<div class="subtitle-meta">
    Creado: <span class="highlight">{{ $fechaPedido ? $fechaPedido->format('d/m/Y H:i') : '-' }}</span>
    &nbsp;|&nbsp;
    Usuario: <span class="highlight">{{ optional($pedido->user)->name ?: '-' }}</span>
    &nbsp;|&nbsp;
    Estado: <span class="highlight">{{ $pedido->estado }}</span>
</div>

<table class="info">
    <tr>
        <td class="cell label">Lugar</td>
        <td class="cell uppercase">Oruro</td>
        <td class="cell label">Fecha del pedido</td>
        <td class="cell">{{ $fechaPedido ? $fechaPedido->format('d - M - Y') : '-' }}</td>
    </tr>
    <tr>
        <td class="cell label">Solicitante</td>
        <td class="cell uppercase">{{ $solicitante }}</td>
        <td class="cell label">Servicio solicitante</td>
        <td class="cell uppercase">{{ $servicioSolicitante }}</td>
    </tr>
    <tr>
        <td class="cell label">Resp. de pedido</td>
        <td class="cell uppercase">{{ $respPedido }}</td>
        <td class="cell label">Items</td>
        <td class="cell">{{ count($pedido->detalles) }}</td>
    </tr>
</table>

<table class="items" style="margin-top:8px;">
    <thead>
        <tr>
            <th style="width:6%;">Item</th>
            <th style="width:10%;">Cantidad</th>
            <th style="width:10%;">Unidad</th>
            <th style="width:50%;">Articulo</th>
            <th style="width:12%;">Precio Unit.</th>
            <th style="width:12%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pedido->detalles as $idx => $det)
            <tr>
                <td class="right">{{ $idx + 1 }}</td>
                <td class="right">{{ $det->cantidad }}</td>
                <td class="uppercase">{{ optional($det->producto)->unidad_medida ?: 'UND' }}</td>
                <td class="uppercase">{{ optional($det->producto)->nombre ?? '-' }}</td>
                <td class="right">{{ number_format((float) $det->precio_unitario, 2, ',', '.') }}</td>
                <td class="right">{{ number_format((float) $det->subtotal, 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center">Sin productos</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="total-row">
    <tr>
        <td class="label">Total:</td>
        <td class="value">{{ number_format((float) $pedido->total, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td colspan="2" class="son"><span class="bold">Son:</span>&nbsp;{{ numero_a_letras((float) $pedido->total) }}</td>
    </tr>
</table>

<div class="print-date">Fecha de Impresion: {{ now()->format('m/d/Y h:i A') }}</div>

<table class="signatures">
    <tr>
        <td>
            @if($firmaPedidoPath && file_exists($firmaPedidoPath))
                <img class="firma-img" src="{{ $firmaPedidoPath }}" alt="Firma">
            @else
                <div class="firma-space"></div>
            @endif
            <div class="line"></div>
            <div class="sign-title">Servicio solicitantes</div>
            <div class="sign-name">{{ $solicitante }}</div>
            @if($selloPedidoPath && file_exists($selloPedidoPath))
                <img class="sello-img" src="{{ $selloPedidoPath }}" alt="Sello">
            @endif
        </td>
        <td>
            <div class="line"></div>
            <div class="sign-title">Resp. de almacen</div>
            <div class="sign-name">Juan Mario Rocha Arispe</div>
        </td>
{{--        <td>--}}
{{--            <div class="line"></div>--}}
{{--            <div class="sign-title">Resp. de recepcion</div>--}}
{{--            <div class="sign-name">{{ optional($pedido->user)->name ?: '-' }}</div>--}}
{{--        </td>--}}
        <td>
            <div class="line"></div>
            <div class="sign-title">Administrador</div>
            <div class="sign-name">Hospital General</div>
        </td>
    </tr>
</table>

</body>
</html>
