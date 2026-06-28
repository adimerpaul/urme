<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Total Valorado - PEPS</title>
    <style>
        @page { margin: 14px 18px; }

        body {
            font-family: "DejaVu Sans", Helvetica, Arial, sans-serif;
            color: #172033;
            font-size: 8.5px;
            line-height: 1.2;
        }

        .page-break { page-break-after: always; }

        .title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f5ea8;
            margin-bottom: 2px;
            letter-spacing: .5px;
        }
        .subtitle {
            text-align: center;
            font-size: 8px;
            color: #475569;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 10px;
            font-weight: bold;
            color: #0f5ea8;
            text-transform: uppercase;
            padding: 4px 6px;
            background: #edf5ff;
            border: 1px solid #7fa1ca;
            margin-bottom: 4px;
            margin-top: 10px;
        }
        .product-title span {
            font-size: 8px;
            color: #475569;
            font-weight: normal;
            margin-left: 8px;
        }

        table { width: 100%; border-collapse: collapse; }

        .peps-table th {
            background: #0f5ea8;
            color: #fff;
            border: 1px solid #0a4a8a;
            padding: 3px 4px;
            text-align: center;
            font-size: 8px;
            text-transform: uppercase;
        }
        .peps-table th.group-header {
            background: #1a73c4;
            font-size: 9px;
            letter-spacing: .3px;
        }
        .peps-table th.fecha-col { width: 7%; }
        .peps-table th.concepto-col { width: 16%; text-align: left; padding-left: 5px; }
        .peps-table th.num-col { width: 7%; }

        .peps-table td {
            border: 1px solid #b8d0ea;
            padding: 2.5px 4px;
            vertical-align: middle;
        }
        .peps-table tbody tr:nth-child(even) td { background: #f5f9ff; }
        .peps-table tbody tr:nth-child(odd) td { background: #fff; }

        .peps-table td.fecha { text-align: center; font-size: 7.5px; }
        .peps-table td.concepto { text-align: left; font-size: 7.5px; text-transform: uppercase; }
        .peps-table td.num { text-align: right; }
        .peps-table td.num-zero { text-align: right; color: #aaa; }

        .peps-table tr.entrada td { }
        .peps-table tr.salida td { }

        .separator-entrada { border-left: 2px solid #2563eb !important; }
        .separator-salida { border-left: 2px solid #dc2626 !important; }
        .separator-saldo { border-left: 2px solid #16a34a !important; }

        .totals-row td {
            background: #e8f1fb !important;
            font-weight: bold;
            border-top: 2px solid #0f5ea8;
        }
        .grand-total-row td {
            background: #0f5ea8 !important;
            color: #fff !important;
            font-weight: bold;
            font-size: 9px;
        }

        .summary-table {
            width: 55%;
            margin-left: auto;
            margin-top: 6px;
            margin-bottom: 4px;
        }
        .summary-table td {
            border: 1px solid #b8d0ea;
            padding: 3px 6px;
            font-size: 8px;
        }
        .summary-table td.label {
            background: #edf5ff;
            color: #0f5ea8;
            font-weight: bold;
            text-transform: uppercase;
            width: 55%;
        }
        .summary-table td.value {
            text-align: right;
            font-weight: bold;
        }
        .summary-table tr.costo td {
            background: #0f5ea8;
            color: #fff;
            font-size: 9px;
        }

        .logo { height: 46px; width: auto; display: block; }
        .header-table td { vertical-align: middle; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
@php
    $logoPath = public_path('img/logo-hospital.png');
    $periodoLabel = ($dateFrom || $dateTo)
        ? 'Período: ' . ($dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '...') . ' — ' . ($dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '...')
        : 'Período: Todo el registro';
@endphp

<table class="header-table">
    <tr>
        <td style="width:15%;">
            @if (file_exists($logoPath))
                <img class="logo" src="{{ $logoPath }}" alt="Logo">
            @endif
        </td>
        <td style="width:70%; text-align:center;">
            <div class="title">Reporte Total Valorado — Método PEPS</div>
            <div class="subtitle">Primeras Entradas, Primeras Salidas &nbsp;|&nbsp; {{ $periodoLabel }}</div>
        </td>
        <td style="width:15%; text-align:right; font-size:7.5px; color:#475569;">
            Generado: {{ now()->format('d/m/Y H:i') }}
        </td>
    </tr>
</table>

@foreach ($cards as $cardIdx => $card)
    <div class="product-title">
        {{ $card['producto'] }}
        <span>Unidad: {{ $card['unidad'] }}</span>
    </div>

    <table class="peps-table">
        <thead>
            <tr>
                <th class="group-header fecha-col" rowspan="2">Fecha</th>
                <th class="group-header concepto-col" rowspan="2">Concepto</th>
                <th class="group-header separator-entrada" colspan="3">Entradas</th>
                <th class="group-header separator-salida" colspan="3">Salidas</th>
                <th class="group-header separator-saldo" colspan="3">Saldos</th>
            </tr>
            <tr>
                <th class="separator-entrada num-col">Cant.</th>
                <th class="num-col">V. Unit.</th>
                <th class="num-col">V. Total</th>
                <th class="separator-salida num-col">Cant.</th>
                <th class="num-col">V. Unit.</th>
                <th class="num-col">V. Total</th>
                <th class="separator-saldo num-col">Cant.</th>
                <th class="num-col">V. Unit.</th>
                <th class="num-col">V. Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($card['rows'] as $row)
                @php
                    $isEntrada = $row['tipo'] === 'ENTRADA';
                    $fecha = $row['fecha'] ? \Carbon\Carbon::parse($row['fecha'])->format('d/m/Y') : '-';
                @endphp
                <tr class="{{ strtolower($row['tipo']) }}">
                    <td class="fecha">{{ $fecha }}</td>
                    <td class="concepto">{{ $row['concepto'] }}</td>

                    {{-- Entradas --}}
                    @if ($isEntrada)
                        <td class="num separator-entrada">{{ $row['cantidad'] }}</td>
                        <td class="num">{{ number_format($row['precio_unitario'], 4, ',', '.') }}</td>
                        <td class="num">{{ number_format($row['total'], 2, ',', '.') }}</td>
                        <td class="num-zero separator-salida">—</td>
                        <td class="num-zero">—</td>
                        <td class="num-zero">—</td>
                    @else
                        <td class="num-zero separator-entrada">—</td>
                        <td class="num-zero">—</td>
                        <td class="num-zero">—</td>
                        <td class="num separator-salida">{{ $row['cantidad'] }}</td>
                        <td class="num">{{ number_format($row['precio_unitario'], 4, ',', '.') }}</td>
                        <td class="num">{{ number_format($row['total'], 2, ',', '.') }}</td>
                    @endif

                    {{-- Saldos --}}
                    <td class="num separator-saldo">{{ $row['saldo_cantidad'] }}</td>
                    <td class="num">{{ number_format($row['saldo_precio_unitario'], 4, ',', '.') }}</td>
                    <td class="num">{{ number_format($row['saldo_total'], 2, ',', '.') }}</td>
                </tr>
            @endforeach

            {{-- Totals row --}}
            <tr class="totals-row">
                <td colspan="2" class="concepto"><strong>TOTALES</strong></td>
                <td class="num separator-entrada"><strong>{{ $card['summary']['total_entradas_cantidad'] }}</strong></td>
                <td class="num">—</td>
                <td class="num"><strong>{{ number_format($card['summary']['total_entradas_valor'], 2, ',', '.') }}</strong></td>
                <td class="num separator-salida"><strong>{{ $card['summary']['total_salidas_cantidad'] }}</strong></td>
                <td class="num">—</td>
                <td class="num"><strong>{{ number_format($card['summary']['total_salidas_valor'], 2, ',', '.') }}</strong></td>
                <td class="num separator-saldo"><strong>{{ $card['summary']['saldo_final_cantidad'] }}</strong></td>
                <td class="num">—</td>
                <td class="num"><strong>{{ number_format($card['summary']['saldo_final_valor'], 2, ',', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td class="label">Total Entradas (Bs.)</td>
            <td class="value">{{ number_format($card['summary']['total_entradas_valor'], 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Total Salidas (Bs.)</td>
            <td class="value">{{ number_format($card['summary']['total_salidas_valor'], 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Saldo Final (cant.)</td>
            <td class="value">{{ $card['summary']['saldo_final_cantidad'] }}</td>
        </tr>
        <tr>
            <td class="label">Saldo Final (Bs.)</td>
            <td class="value">{{ number_format($card['summary']['saldo_final_valor'], 2, ',', '.') }}</td>
        </tr>
        <tr class="costo">
            <td><strong>Costo de Ventas (Bs.)</strong></td>
            <td class="value right"><strong>{{ number_format($card['summary']['costo_ventas'], 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
