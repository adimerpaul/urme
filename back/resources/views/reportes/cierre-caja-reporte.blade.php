<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de cierre de caja</title>
    <style>
        @page { margin: 18px 22px 30px; size: letter; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 8.5px; line-height: 1.35; }
        .header { border-bottom: 2px solid #00695C; padding-bottom: 5px; margin-bottom: 8px; overflow: hidden; }
        .brand { color: #00695C; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 2px 0 0; font-size: 15px; color: #111827; }
        .meta { float: right; color: #64748b; font-size: 7.5px; text-align: right; }
        h2 { font-size: 10px; color: #fff; background: #00695C; padding: 3px 6px; margin: 10px 0 4px; text-transform: uppercase; }
        h3 { font-size: 8.5px; color: #004D40; margin: 6px 0 2px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2.5px 5px; border-bottom: 1px solid #dbe4ee; vertical-align: top; }
        th { background: #E0F2F1; color: #004D40; font-size: 7.5px; text-transform: uppercase; text-align: left; }
        .num { text-align: right; white-space: nowrap; }
        .total td { font-weight: bold; border-top: 1.5px solid #00695C; }
        .grande td { font-size: 10px; font-weight: bold; color: #004D40; }
        .ficha td { border: 0; padding: 1px 6px; }
        .ficha .l { color: #546E7A; width: 22%; }
        .dos { width: 100%; } .dos > tbody > tr > td { width: 50%; border: 0; padding: 0 6px 0 0; }
        .nota { color: #64748b; font-size: 7.5px; margin-top: 3px; }
        .aviso { background: #FFF8E1; border: 1px solid #FFCC80; padding: 4px 6px; margin-top: 4px; font-size: 7.5px; }
        .ok { color: #2E7D32; font-weight: bold; } .mal { color: #C62828; font-weight: bold; }
        .det { color: #475569; font-size: 7px; }
        .seccion { page-break-inside: avoid; }
        .firmas { margin-top: 40px; } .firmas td { border: 0; text-align: center; width: 50%; padding: 0 30px; }
        .linea { border-top: 1px solid #374151; padding-top: 3px; }
    </style>
</head>
<body>
@php
    $bs = fn ($v) => number_format((float) $v, 2);
    $fecha = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y H:i') : '—';
    $dif = fn ($v) => $v > 0 ? 'Sobrante '.$bs($v) : ($v < 0 ? 'Faltante '.$bs(-$v) : 'Sin diferencia');
    $e = $r['encabezado']; $a = $r['arqueo']; $v = $r['ventas']; $g = $r['gastos']; $c = $r['controles'];
@endphp
    <div class="header">
        <div class="meta">Generado: {{ $fecha($e['generado_en']) }}<br>Cierre N° {{ str_pad($e['cierre_id'], 6, '0', STR_PAD_LEFT) }}</div>
        <div class="brand">Clínica URME · Caja</div>
        <h1>Reporte de cierre de caja</h1>
    </div>

    {{-- 1. Encabezado --}}
    <table class="ficha">
        <tr><td class="l">Cajero</td><td><b>{{ $e['cajero'] }}</b></td><td class="l">Jornada</td><td><b>{{ \Carbon\Carbon::parse($e['fecha'])->format('d/m/Y') }}</b></td></tr>
        <tr><td class="l">Cerrado el</td><td>{{ $fecha($e['cerrado_en']) }}</td><td class="l">Corrección</td><td>{{ $fecha($e['corregido_en']) }}</td></tr>
        <tr><td class="l">Estado</td><td><b>{{ $e['estado'] }}</b></td><td class="l">Validado por</td><td>{{ $e['validado_por'] ?? '—' }} · {{ $fecha($e['validado_en']) }}</td></tr>
        @if ($e['autorizado_por'])
            <tr><td class="l">Tiempo autorizado</td><td colspan="3">hasta {{ $fecha($e['autorizado_hasta']) }} por {{ $e['autorizado_por'] }}</td></tr>
        @endif
        @if ($e['comentario'])
            <tr><td class="l">Comentario</td><td colspan="3">{{ $e['comentario'] }}</td></tr>
        @endif
    </table>

    {{-- 2. Arqueo de efectivo --}}
    <div class="seccion">
        <h2>1. Arqueo de efectivo</h2>
        <table class="dos"><tr><td>
            <table>
                <tr><td>Efectivo de las ventas</td><td class="num">{{ $bs($a['efectivo_ingresos']) }}</td></tr>
                <tr><td>(-) Gastos en efectivo ({{ $a['gastos_cantidad'] }})</td><td class="num">{{ $bs($a['gastos_efectivo']) }}</td></tr>
                <tr class="total"><td>Efectivo esperado</td><td class="num">{{ $bs($a['efectivo_esperado']) }}</td></tr>
                <tr><td>Efectivo declarado</td><td class="num">{{ $bs($a['declarado']) }}</td></tr>
                <tr class="grande"><td>Diferencia de efectivo</td><td class="num">{{ $dif($a['diferencia_efectivo']) }}</td></tr>
            </table>
        </td><td>
            <h3>Desglose del efectivo declarado</h3>
            @if ($a['desglose'])
                <table>
                    <tr><th>Corte (Bs)</th><th class="num">Cantidad</th><th class="num">Subtotal</th></tr>
                    @foreach ($a['desglose'] as $d)
                        <tr><td>{{ $bs($d['corte']) }}</td><td class="num">{{ $d['cantidad'] }}</td><td class="num">{{ $bs($d['subtotal']) }}</td></tr>
                    @endforeach
                </table>
            @else
                <div class="nota">El cajero no registró el conteo por cortes.</div>
            @endif
        </td></tr></table>
        @if ($a['formula_anterior'])
            <div class="aviso">
                <b>Fórmula anterior.</b> Diferencia registrada al cerrar: {{ $dif($a['diferencia_registrada']) }}
                (contra el total del sistema, {{ $bs($a['monto_sistema_registrado']) }} Bs, QR incluido).<br>{{ $a['nota'] }}
            </div>
        @endif
    </div>

    {{-- 3. Otros medios de pago --}}
    <div class="seccion">
        <h2>2. Otros medios de pago (no entran en el arqueo)</h2>
        <table>
            <tr><th>Medio</th><th class="num">Transacciones</th><th class="num">Total (Bs)</th></tr>
            <tr><td>QR (incluye la parte QR de los pagos mixtos)</td><td class="num">{{ $r['otros_medios']['qr_cantidad'] }}</td><td class="num">{{ $bs($r['otros_medios']['qr_total']) }}</td></tr>
            @foreach ($r['otros_medios']['otros'] as $o)
                <tr><td>{{ $o['medio'] }}</td><td class="num">{{ $o['cantidad'] }}</td><td class="num">{{ $bs($o['total']) }}</td></tr>
            @endforeach
        </table>
    </div>

    {{-- 4. Resumen de ventas --}}
    <div class="seccion">
        <h2>3. Resumen de ventas · {{ $v['cantidad'] }} ventas · Bs {{ $bs($v['total']) }}</h2>
        <table class="dos"><tr><td>
            <h3>Por forma de pago</h3>
            <table>
                @foreach ($v['por_forma_de_pago']['filas'] as $f)
                    <tr><td>{{ $f['nombre'] }}</td><td class="num">{{ $f['cantidad'] }}</td><td class="num">{{ $bs($f['total']) }}</td></tr>
                @endforeach
                <tr class="total"><td>Total</td><td></td><td class="num">{{ $bs($v['por_forma_de_pago']['total']) }}</td></tr>
            </table>
            <h3>Por tipo de paciente</h3>
            <table>
                @foreach ($v['por_tipo_paciente'] as $f)
                    <tr><td>{{ $f['nombre'] }}</td><td class="num">{{ $f['cantidad'] }}</td><td class="num">{{ $bs($f['total']) }}</td></tr>
                @endforeach
                <tr class="total"><td>Total</td><td></td><td class="num">{{ $bs(collect($v['por_tipo_paciente'])->sum('total')) }}</td></tr>
            </table>
        </td><td>
            <h3>Por categoría</h3>
            <table>
                @foreach ($v['por_categoria'] as $f)
                    <tr><td>{{ $f['nombre'] }}</td><td class="num">{{ $f['cantidad'] }}</td><td class="num">{{ $bs($f['total']) }}</td></tr>
                @endforeach
                <tr class="total"><td>Total</td><td></td><td class="num">{{ $bs(collect($v['por_categoria'])->sum('total')) }}</td></tr>
            </table>
            <h3>Incluidos en el total</h3>
            <table>
                <tr><td>Cobros de internación</td><td class="num">{{ $v['internaciones']['cantidad'] }}</td><td class="num">{{ $bs($v['internaciones']['total']) }}</td></tr>
                <tr><td>Pendientes de otros días u otros usuarios cobradas hoy</td><td class="num">{{ $v['pendientes_otros_dias']['cantidad'] }}</td><td class="num">{{ $bs($v['pendientes_otros_dias']['total']) }}</td></tr>
            </table>
        </td></tr></table>
    </div>

    {{-- 5. Gastos --}}
    <div class="seccion">
        <h2>4. Gastos · {{ $g['cantidad'] }} · Bs {{ $bs($g['total']) }}</h2>
        @if ($g['lista'])
            <table>
                <tr><th>N°</th><th>Hora</th><th>Descripción</th><th>Categoría</th><th>Subcategoría</th><th class="num">Monto (Bs)</th></tr>
                @foreach ($g['lista'] as $gasto)
                    <tr><td>{{ $gasto['id'] }}</td><td>{{ $gasto['hora'] }}</td><td>{{ $gasto['descripcion'] }}</td><td>{{ $gasto['categoria'] }}</td><td>{{ $gasto['subcategoria'] ?? '—' }}</td><td class="num">{{ $bs($gasto['total']) }}</td></tr>
                @endforeach
            </table>
            <h3>Por categoría</h3>
            <table>
                @foreach ($g['por_categoria'] as $f)
                    <tr><td>{{ $f['categoria'] }}</td><td class="num">{{ $f['cantidad'] }}</td><td class="num">{{ $bs($f['total']) }}</td></tr>
                @endforeach
            </table>
        @else
            <div class="nota">Sin gastos en esta jornada.</div>
        @endif
    </div>

    {{-- 6. Controles --}}
    <div class="seccion">
        <h2>5. Controles</h2>
        <h3>Ventas anuladas del día (no se suman)</h3>
        @if ($c['anuladas'])
            <table>
                <tr><th>N°</th><th>Hora</th><th>Cliente</th><th>Anulada el</th><th>Por</th><th>Cuándo</th><th class="num">Total (Bs)</th></tr>
                @foreach ($c['anuladas'] as $x)
                    <tr><td>{{ $x['id'] }}</td><td>{{ $x['hora'] }}</td><td>{{ $x['cliente'] }}</td><td>{{ $fecha($x['anulado_en']) }}@if ($x['fuente'] !== 'registro') <span class="det">({{ $x['fuente'] }})</span>@endif</td>
                        <td>{{ $x['anulado_por'] ?? '—' }}</td><td>@if ($x['despues_del_cierre'])<span class="mal">DESPUÉS DEL CIERRE</span>@else antes del cierre @endif</td><td class="num">{{ $bs($x['total']) }}</td></tr>
                @endforeach
            </table>
        @else
            <div class="nota">Ninguna.</div>
        @endif
        <h3>Ventas con precio modificado (distinto al de lista)</h3>
        @if ($c['precio_modificado'])
            <table>
                <tr><th>Venta</th><th>Ítem</th><th class="num">Cant.</th><th class="num">Lista</th><th class="num">Cobrado</th><th class="num">Diferencia</th></tr>
                @foreach ($c['precio_modificado'] as $x)
                    <tr><td>{{ $x['venta_id'] }}</td><td>{{ $x['item'] }}</td><td class="num">{{ $x['cantidad'] }}</td><td class="num">{{ $bs($x['precio_lista']) }}</td><td class="num">{{ $bs($x['precio_cobrado']) }}</td><td class="num">{{ $bs($x['diferencia']) }}</td></tr>
                @endforeach
            </table>
        @else
            <div class="nota">Ninguna.</div>
        @endif
        <h3>Pendientes sin cobrar al cierre</h3>
        @if ($c['pendientes_sin_cobrar'])
            <table>
                <tr><th>N°</th><th>Hora</th><th>Cliente</th><th>Cobrada después</th><th class="num">Total (Bs)</th></tr>
                @foreach ($c['pendientes_sin_cobrar'] as $x)
                    <tr><td>{{ $x['id'] }}</td><td>{{ $x['hora'] }}</td><td>{{ $x['cliente'] }}</td><td>{{ $fecha($x['cobrada_despues']) }}</td><td class="num">{{ $bs($x['total']) }}</td></tr>
                @endforeach
            </table>
        @else
            <div class="nota">Ninguna.</div>
        @endif
        <h3>Cuadres del reporte</h3>
        <table>
            @foreach ($r['cuadres'] as $x)
                <tr><td>{{ $x['texto'] }}</td><td class="num">@if ($x['ok'])<span class="ok">CUADRA</span>@else<span class="mal">NO CUADRA</span>@endif</td></tr>
            @endforeach
        </table>
    </div>

    {{-- 7. Detalle --}}
    <h2>6. Detalle de movimientos</h2>
    <table>
        <thead><tr><th>N°</th><th>Hora</th><th>Cliente / Paciente</th><th>Ítems</th><th>Pago</th><th class="num">Efectivo</th><th class="num">QR</th><th class="num">Otros</th><th>Estado</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach ($r['detalle'] as $x)
            <tr>
                <td>{{ $x['id'] }}</td><td>{{ $x['hora'] }}</td><td>{{ $x['tipo'] === 'GASTO' ? 'GASTO: ' : '' }}{{ $x['cliente'] }}</td>
                <td>@foreach ($x['items'] as $i)<div class="det">{{ $i['cantidad'] }} x {{ $i['nombre'] }} @ {{ $bs($i['precio']) }} = {{ $bs($i['subtotal']) }}</div>@endforeach</td>
                <td>{{ $x['tipo_pago'] }}</td>
                <td class="num">{{ $bs($x['tipo'] === 'GASTO' ? -$x['efectivo'] : $x['efectivo']) }}</td>
                <td class="num">{{ $bs($x['qr']) }}</td><td class="num">{{ $bs($x['otros']) }}</td>
                <td>{{ $x['estado'] }}</td><td class="num">{{ $bs($x['total']) }}</td>
            </tr>
        @endforeach
        <tr class="total"><td colspan="9" class="num">Ventas menos gastos</td><td class="num">{{ $bs(collect($r['detalle'])->sum('total')) }}</td></tr>
        </tbody>
    </table>

    {{-- 8. Firmas --}}
    <table class="firmas"><tr>
        <td><div class="linea">Entregué conforme<br><b>{{ $r['firmas']['cajero'] }}</b><br>Cajero</div></td>
        <td><div class="linea">Recibí y validé conforme<br><b>{{ $r['firmas']['supervisor'] ?? '' }}</b><br>Supervisor</div></td>
    </tr></table>
</body>
</html>
