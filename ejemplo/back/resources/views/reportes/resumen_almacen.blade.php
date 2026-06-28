<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>DGCF-R1.05 Resumen de Almacenes</title>
<style>
@page { size: A4 landscape; margin: 14px 18px; }
body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 8.5px; color: #172033; line-height: 1.2; }
.code-row { display: flex; justify-content: space-between; font-size: 7.5px; color: #555; margin-bottom: 3px; }
.hosp { text-align: center; font-size: 12px; font-weight: bold; text-transform: uppercase; color: #0f5ea8; }
.titulo { text-align: center; font-size: 10px; font-weight: bold; text-transform: uppercase; margin: 1px 0; }
.subtitulo { text-align: center; font-size: 8px; color: #475569; margin-bottom: 6px; }
table { width: 100%; border-collapse: collapse; }
th { background: #0f5ea8; color: #fff; padding: 4px 5px; text-align: center; font-size: 8px; border: 1px solid #0a4a8a; }
td { border: 1px solid #b8d0ea; padding: 2.5px 5px; vertical-align: middle; font-size: 8px; }
tr:nth-child(even) td { background: #f5f9ff; }
.num { text-align: right; }
.center { text-align: center; }
.total-row td { background: #0f5ea8 !important; color: #fff; font-weight: bold; font-size: 9px; }
</style>
</head>
<body>

<table style="margin-bottom:4px; border:none;">
  <tr>
    <td style="border:none; width:15%;">
      @if(file_exists(public_path('img/logo-hospital.png')))
        <img src="{{ public_path('img/logo-hospital.png') }}" style="height:44px;">
      @endif
    </td>
    <td style="border:none; text-align:center;">
      <div class="hosp">Hospital General San Juan de Dios Oruro</div>
      <div class="titulo">Resumen de Almacenes y Farmacia (Bienes de Consumo)</div>
      <div class="subtitulo">{{ $meta['periodo'] ?: 'Del 01 de Enero al 31 de Diciembre de 2025' }} &nbsp;|&nbsp; (Expresado en Bolivianos)</div>
    </td>
    <td style="border:none; width:15%; text-align:right; font-size:7.5px; color:#555;">
      DGCF-R1.05<br>Versión 01<br>Generado: {{ now()->format('d/m/Y H:i') }}
    </td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th style="width:4%;">Nº</th>
      <th style="text-align:left;">DETALLE</th>
      <th style="width:9%;">Partida</th>
      <th style="width:14%;">Cantidad Inicial<br>al {{ \Carbon\Carbon::parse($meta['desde'])->format('d/m/Y') }}</th>
      <th style="width:16%;">Saldo Inicial<br>al {{ \Carbon\Carbon::parse($meta['desde'])->format('d/m/Y') }} (Bs)</th>
      <th style="width:14%;">Cantidad Final<br>al {{ \Carbon\Carbon::parse($meta['hasta'])->format('d/m/Y') }}</th>
      <th style="width:16%;">Saldo Final<br>al {{ \Carbon\Carbon::parse($meta['hasta'])->format('d/m/Y') }} (Bs)</th>
    </tr>
  </thead>
  <tbody>
    @foreach($rows as $row)
    <tr>
      <td class="center">{{ $row['nro'] }}</td>
      <td>{{ $row['detalle'] }}</td>
      <td class="center">{{ $row['partida'] }}</td>
      <td class="num">{{ number_format($row['cant_ini'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['saldo_ini'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['cant_final'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['saldo_final'], 2, ',', '.') }}</td>
    </tr>
    @endforeach
    @if($total)
    <tr class="total-row">
      <td colspan="3" class="center"><strong>TOTAL</strong></td>
      <td class="num"><strong>{{ number_format($total['cant_ini'], 2, ',', '.') }}</strong></td>
      <td class="num"><strong>{{ number_format($total['saldo_ini'], 2, ',', '.') }}</strong></td>
      <td class="num"><strong>{{ number_format($total['cant_final'], 2, ',', '.') }}</strong></td>
      <td class="num"><strong>{{ number_format($total['saldo_final'], 2, ',', '.') }}</strong></td>
    </tr>
    @endif
  </tbody>
</table>

</body>
</html>
