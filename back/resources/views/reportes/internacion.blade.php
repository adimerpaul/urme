<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Proforma de pago</title>
    <style>
        @page { margin: 20px 24px; size: letter portrait; }
        body { font-family: Helvetica, Arial, sans-serif; color: #172033; font-size: 10px; line-height: 1.35; }
        .header { border-bottom: 3px solid #0D47A1; padding-bottom: 8px; margin-bottom: 12px; overflow: hidden; }
        .brand { color: #0D47A1; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        h1 { margin: 3px 0 0; font-size: 19px; color: #0D47A1; text-transform: uppercase; letter-spacing: 0.5px; }
        .meta { float: right; color: #64748b; font-size: 8.5px; text-align: right; }

        table.info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.info td { border: 1px solid #90CAF9; padding: 5px 8px; font-size: 9.5px; }
        table.info td.label { background: #E3F2FD; color: #0D47A1; font-weight: bold; width: 26%; }

        .section-title { background: #0D47A1; color: #fff; font-size: 9.5px; font-weight: bold; text-transform: uppercase; padding: 5px 8px; margin-top: 10px; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 0; }
        table.items th { background: #BBDEFB; color: #0D47A1; font-size: 8.5px; font-weight: bold; text-transform: uppercase; padding: 5px 6px; text-align: left; border-bottom: 2px solid #0D47A1; }
        table.items td { padding: 4px 6px; border-bottom: 1px solid #dbe4ee; font-size: 9px; }
        table.items tbody tr:nth-child(even) td { background: #F5F9FF; }
        .c-cant { width: 10%; text-align: center; }
        .c-prec { width: 15%; text-align: right; }
        .c-tot  { width: 15%; text-align: right; }
        .c-por  { width: 20%; }

        .empty { border: 1px dashed #90CAF9; color: #64748b; padding: 16px; text-align: center; margin-top: 4px; }

        .totales { width: 100%; margin-top: 10px; }
        .totales td { padding: 5px 8px; font-size: 10.5px; }
        .totales .lbl { text-align: right; color: #0D47A1; font-weight: bold; }
        .totales .val { text-align: right; width: 110px; font-weight: bold; }
        .totales .grand td { border-top: 2px solid #0D47A1; font-size: 13px; color: #0D47A1; }

        .firma { margin-top: 40px; }
        .firma-box { display: inline-block; width: 45%; border-top: 1px solid #64748b; padding-top: 4px; font-size: 9px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="meta">Generado: {{ now()->format('d/m/Y H:i') }}</div>
        <div class="brand">Clínica URME</div>
        <h1>Proforma de pago</h1>
    </div>

    <table class="info">
        <tr>
            <td class="label">Paciente</td>
            <td colspan="3">{{ $internacion->paciente->nombre_completo }}</td>
        </tr>
        <tr>
            <td class="label">Fecha de ingreso</td>
            <td>{{ $internacion->fecha_ingreso ?: '—' }}</td>
            <td class="label">Fecha de alta</td>
            <td>{{ $internacion->fecha_alta ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Total días</td>
            <td>{{ $internacion->dias_internado ?? '—' }}</td>
            <td class="label">Tipo de paciente</td>
            <td>{{ $internacion->tipo_paciente ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Código H.C.</td>
            <td>{{ $internacion->codigo_hc ?: '—' }}</td>
            <td class="label">Sala</td>
            <td>{{ $internacion->sala ?: '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Detalle de internación</div>

    @if ($internacion->items->isEmpty())
        <div class="empty">Sin cargos registrados.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th>Ítem</th>
                    <th class="c-cant">Cantidad</th>
                    <th class="c-prec">Precio</th>
                    <th class="c-tot">Total Bs.</th>
                    <th class="c-por">Registrado por</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($internacion->items as $item)
                    <tr>
                        <td>{{ $item->nombre }}</td>
                        <td class="c-cant">{{ rtrim(rtrim(number_format($item->cantidad, 2, '.', ''), '0'), '.') }}</td>
                        <td class="c-prec">{{ number_format($item->precio, 2, ',', '.') }}</td>
                        <td class="c-tot">{{ number_format($item->total, 2, ',', '.') }}</td>
                        <td class="c-por">{{ $item->user->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="totales">
        <tr class="grand">
            <td class="lbl">TOTAL CUENTA</td>
            <td class="val">Bs. {{ number_format($total, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="firma">
        <div class="firma-box">Firma</div>
    </div>
</body>
</html>
