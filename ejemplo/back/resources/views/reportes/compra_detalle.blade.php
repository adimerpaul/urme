<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ingreso de materiales #{{ $compra->id }}</title>
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
        .mt-6 { margin-top: 6px; }
        .mt-10 { margin-top: 10px; }
        .mt-14 { margin-top: 14px; }
        .border { border: 1px solid #7fa1ca; }
        .cell { border: 1px solid #7fa1ca; padding: 3px 5px; vertical-align: middle; }
        .header-cell { border: 1px solid #7fa1ca; padding: 4px 5px; font-weight: bold; text-transform: uppercase; }
        .logo { height: 58px; width: auto; display: block; margin-bottom: 3px; }
        .city { font-style: italic; font-size: 11px; color: #0f5ea8; }

        .doc-head td { vertical-align: top; }
        .doc-number-table { width: 70%; margin-left: auto; border-collapse: collapse; }
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

        .obs td {
            border: 1px solid #7fa1ca;
            padding: 4px 5px;
        }
        .obs-label { color: #0f5ea8; background: #edf5ff; }

        .totals-wrap {
            width: 44%;
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
        .clearfix { clear: both; }

        .signatures {
            width: 100%;
            margin-top: 18px;
        }
        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 12px;
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
        }
        .firma-img { max-height: 42px; max-width: 130px; margin-bottom: -8px; }
        .sello-img { max-height: 44px; max-width: 120px; margin-top: 3px; }
        .firma-space { height: 42px; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('img/logo-hospital.png');
    $proveedor = optional($compra->proveedor)->nombre ?? $compra->nombre ?? 'SIN PROVEEDOR';
    $carnetNit = optional($compra->proveedor)->carnet ?? $compra->carnet ?? '-';
    $fechaCompra = $compra->fecha_hora ? \Carbon\Carbon::parse($compra->fecha_hora) : null;
    $compraUser = $compra->user;
    $firmaCompraPath = $compraUser && $compraUser->mostrar_firma && $compraUser->firma ? public_path('images/'.$compraUser->firma) : null;
    $selloCompraPath = $compraUser && $compraUser->mostrar_sello && $compraUser->sello ? public_path('images/'.$compraUser->sello) : null;
@endphp

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
                    <td class="center">{{ $compra->numero ?: $compra->id }}</td>
                </tr>
                <tr>
                    <td class="bold uppercase center">H / Ruta</td>
                    <td class="center">{{ $compra->hoja_de_ruta ?: '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="title">Ingreso de materiales</div>
<div class="subtitle-meta">
    Creado: <span class="highlight">{{ $fechaCompra ? $fechaCompra->format('d/m/Y H:i') : '-' }}</span>
    &nbsp;|&nbsp;
{{--    Usuario: <span class="highlight">{{ optional($compra->user)->name ?: '-' }}</span>--}}
</div>

<table class="info-table mt-6">
    <tr>
        <td class="cell label">Factura N&deg;</td>
        <td class="cell">{{ $compra->nro_factura ?: '-' }}</td>
        <td class="cell label">Oruro</td>
        <td class="cell">{{ $fechaCompra ? $fechaCompra->format('d/m/Y') : '-' }}</td>
    </tr>
    <tr>
        <td class="cell label">Categoria</td>
        <td class="cell">{{ strtoupper($compra->categoria_programatica ?: 'NINGUNO') }}</td>
        <td class="cell label">Proveedor</td>
        <td class="cell uppercase">{{ $proveedor }}</td>
    </tr>
    <tr>
        <td class="cell label">Orden compra</td>
        <td class="cell">{{ $compra->numero ?: $compra->id }}</td>
        <td class="cell label">Fecha O.C.</td>
        <td class="cell">{{ $fechaCompra ? $fechaCompra->format('d-m-y') : '-' }}</td>
    </tr>
{{--    <tr>--}}
{{--        <td class="cell label">Unidad solicitante</td>--}}
{{--        <td class="cell uppercase" colspan="3">{{ $compra->motivo_registro ?: '-' }}</td>--}}
{{--    </tr>--}}
    <tr>
        <td class="cell label">Unidad solicitante</td>
        <td class="cell uppercase" colspan="3">{{ optional($compra->unidad)->nombre ?: '-' }}</td>
    </tr>
    <tr>
        <td class="cell label">Carnet / NIT</td>
        <td class="cell">{{ $carnetNit }}</td>
        <td class="cell label">Registrado por</td>
        <td class="cell">
{{--            {{ optional($compra->user)->name ?: '-' }}--}}
        </td>
    </tr>
    <tr>
        <td class="cell label">Estado</td>
        <td class="cell">{{ $compra->estado }}</td>
        <td class="cell label">Tipo registro</td>
        <td class="cell">{{ $compra->tipo_registro }}</td>
    </tr>
</table>

<table class="items-table mt-10">
    <thead>
        <tr>
            <th style="width:5%;">Item</th>
            <th style="width:8%;">Part. Presup.</th>
            <th style="width:7%;">Solicit.</th>
            <th style="width:7%;">Recibida</th>
            <th style="width:8%;">Unidad</th>
            <th style="width:35%;">Descripcion</th>
            <th style="width:9%;">Precio unit.</th>
            <th style="width:9%;">Total Bs.</th>
            <th style="width:6%;">Lote</th>
            <th style="width:6%;">Vence</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($compra->detalles as $idx => $det)
            <tr>
                <td class="center">{{ $idx + 1 }}</td>
                <td class="center tiny">{{ optional(optional($det->producto)->subpartida)->codigo ?? '-' }}</td>
                <td class="center">{{ $det->cantidad }}</td>
                <td class="center">{{ $det->cantidad }}</td>
                <td class="center">{{ strtoupper(optional($det->producto)->unidad_medida ?: 'PZA') }}</td>
                <td class="uppercase">
                    {{ $det->nombre ?? optional($det->producto)->nombre ?? '-' }}
                </td>
                <td class="right">{{ number_format((float) $det->precio, 2, ',', '.') }}</td>
                <td class="right">{{ number_format((float) $det->total, 2, ',', '.') }}</td>
                <td class="center small">{{ $det->lote ?: '-' }}</td>
                <td class="center small">{{ $det->fecha_vencimiento ? \Carbon\Carbon::parse($det->fecha_vencimiento)->format('d/m/y') : '-' }}</td>
            </tr>
        @empty
            <tr>
                <td class="center" colspan="10">Sin productos</td>
            </tr>
        @endforelse
    </tbody>
</table>

@php
    $totalBruto   = (float) $compra->total;
    $retPct       = (float) ($compra->retencion_porcentaje ?? 0);
    $retMonto     = $retPct > 0 ? round($totalBruto * $retPct / 100, 2) : 0;
    $totalNeto    = $totalBruto - $retMonto;
@endphp

<table class="totals-wrap">
    <tr>
        <td class="label">Total</td>
        <td class="value">{{ number_format($totalBruto, 2, ',', '.') }} Bs</td>
    </tr>
    @if($retPct > 0)
    <tr>
        <td class="label">Retención al {{ number_format($retPct, 0) }}%</td>
        <td class="value">{{ number_format($retMonto, 2, ',', '.') }} Bs</td>
    </tr>
    @endif
    <tr class="total">
        <td>{{ $retPct > 0 ? 'Neto a pagar' : 'Total' }}</td>
        <td class="value">{{ number_format($totalNeto, 2, ',', '.') }} Bs</td>
    </tr>
</table>
<div class="clearfix"></div>

<table class="obs">
    <tr>
        <td style="width:16%;" class="bold uppercase obs-label">Observaciones:</td>
        <td>{{ $compra->comentario ?: '-' }}</td>
        <td style="width:13%;" class="right bold">{{ number_format($totalNeto, 2, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="bold uppercase obs-label">Son:</td>
        <td colspan="2" class="uppercase bold">{{ numero_a_letras($totalNeto) }}</td>
    </tr>
</table>

<table class="signatures">
    <tr>
        <td>
{{--            @if($firmaCompraPath && file_exists($firmaCompraPath))--}}
{{--                <img class="firma-img" src="{{ $firmaCompraPath }}" alt="Firma">--}}
{{--            @else--}}
{{--                <div class="firma-space"></div>--}}
{{--            @endif--}}
            <div class="line"></div>
            <div class="bold uppercase">Responsable de la unidad</div>
            <div class="stamp" style="color: white">
                aa
{{--                {{ optional($compra->user)->name ?: '-' }}--}}
            </div>
{{--            @if($selloCompraPath && file_exists($selloCompraPath))--}}
{{--                <img class="sello-img" src="{{ $selloCompraPath }}" alt="Sello">--}}
{{--            @endif--}}
        </td>
        <td>
            <br>
            <br>
            <br>
            <div class="line"></div>
            <div class="bold uppercase">Administrador</div>
            <div class="stamp">Hospital General</div>
        </td>
    </tr>
</table>

</body>
</html>
