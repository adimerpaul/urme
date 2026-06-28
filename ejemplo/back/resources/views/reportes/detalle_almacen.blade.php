<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>DGCF-R1.06 Detalle de Almacenes</title>
<style>
@page { size: A4 landscape; margin: 12px 16px; }
body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 7.5px; color: #172033; line-height: 1.2; }
.hosp { text-align: center; font-size: 11px; font-weight: bold; text-transform: uppercase; color: #0f5ea8; }
.titulo { text-align: center; font-size: 9.5px; font-weight: bold; text-transform: uppercase; margin: 1px 0; }
.subtitulo { text-align: center; font-size: 7.5px; color: #475569; margin-bottom: 5px; }
table { width: 100%; border-collapse: collapse; }
th { background: #0f5ea8; color: #fff; padding: 3px 3px; text-align: center; font-size: 7px; border: 1px solid #0a4a8a; }
th.group-cant { background: #1a73c4; }
th.group-val  { background: #156a3c; }
td { border: 1px solid #b8d0ea; padding: 2px 3px; vertical-align: middle; font-size: 7px; }
tr:nth-child(even) td { background: #f5f9ff; }
.num { text-align: right; }
.center { text-align: center; }
</style>
</head>
<body>

<table style="margin-bottom:4px; border:none;">
  <tr>
    <td style="border:none; width:12%;">
      @if(file_exists(public_path('img/logo-hospital.png')))
        <img src="{{ public_path('img/logo-hospital.png') }}" style="height:40px;">
      @endif
    </td>
    <td style="border:none; text-align:center;">
      <div class="hosp">Hospital General San Juan de Dios Oruro</div>
      <div class="titulo">Detalle de Almacenes-Farmacia (Bienes de Consumo)</div>
      <div class="subtitulo">{{ $meta['periodo'] ?: 'Del 1 de Enero al 31 de Diciembre del 2025' }} &nbsp;|&nbsp; (Expresado en Bolivianos)</div>
    </td>
    <td style="border:none; width:12%; text-align:right; font-size:7px; color:#555;">
      DGCF-R1.06<br>Versión 01<br>Generado: {{ now()->format('d/m/Y H:i') }}
    </td>
  </tr>
</table>

<table>
  <thead>
    <tr>
      <th rowspan="2" style="width:3%;">Nº</th>
      <th rowspan="2" style="text-align:left; width:30%;">Descripción (Item)</th>
      <th rowspan="2" style="width:8%;">Unidad<br>de medida</th>
      <th rowspan="2" style="width:8%;">Precio<br>Unitario</th>
      <th class="group-cant" colspan="4">Cantidad</th>
      <th class="group-val"  colspan="4">Valores</th>
    </tr>
    <tr>
      <th class="group-cant">Saldo Inicial</th>
      <th class="group-cant">Entradas</th>
      <th class="group-cant">Salidas</th>
      <th class="group-cant">Saldo Final</th>
      <th class="group-val">Saldo Inicial</th>
      <th class="group-val">Entradas</th>
      <th class="group-val">Salidas</th>
      <th class="group-val">Saldo Final</th>
    </tr>
  </thead>
  <tbody>
    @foreach($rows as $row)
    <tr>
      <td class="center">{{ $row['nro'] }}</td>
      <td>{{ $row['descripcion'] }}</td>
      <td class="center">{{ $row['unidad'] }}</td>
      <td class="num">{{ number_format($row['precio_unitario'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['cant_saldo_ini'], 0, ',', '.') }}</td>
      <td class="num">{{ number_format($row['cant_entradas'], 0, ',', '.') }}</td>
      <td class="num">{{ number_format($row['cant_salidas'], 0, ',', '.') }}</td>
      <td class="num">{{ number_format($row['cant_saldo_final'], 0, ',', '.') }}</td>
      <td class="num">{{ number_format($row['val_saldo_ini'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['val_entradas'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['val_salidas'], 2, ',', '.') }}</td>
      <td class="num">{{ number_format($row['val_saldo_final'], 2, ',', '.') }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

</body>
</html>
