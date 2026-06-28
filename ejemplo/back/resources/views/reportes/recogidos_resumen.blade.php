<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter landscape; margin: 10px 14px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5px; color: #111; margin: 0; }
        .small-text { font-size: 7px; }

        .header { display: table; width: 100%; margin-bottom: 4px; }
        .header-left { display: table-cell; vertical-align: middle; width: 70%; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; width: 30%; font-size: 8px; color: #555; }

        .title { font-size: 13px; font-weight: 700; }
        .subtitle { font-size: 9px; color: #444; margin-top: 2px; }

        .kpis { margin: 5px 0; padding: 5px 8px; background: #f0f4f8; border-left: 3px solid #2563eb; font-size: 9px; }
        .kpis span { margin-right: 18px; }
        .kpis b { color: #1e40af; }

        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        thead tr { background: #1e3a5f; color: #fff; }
        th { padding: 4px 3px; text-align: center; font-size: 8px; font-weight: 600; border: 1px solid #1e3a5f; }
        td { border: 1px solid #ccd1d9; padding: 3px 4px; vertical-align: middle; }
        tr:nth-child(even) td { background: #f7f9fc; }

        .center { text-align: center; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7.5px; font-weight: 700; }
        .badge-recogido { background: #d1fae5; color: #065f46; }
        .badge-activo    { background: #ffedd5; color: #7c2d12; }
        .badge-pendiente { background: #f1f5f9; color: #475569; }

        .footer { margin-top: 10px; display: table; width: 100%; font-size: 8px; color: #555; }
        .footer-left  { display: table-cell; width: 60%; }
        .footer-right { display: table-cell; text-align: right; width: 40%; }
        .firma { margin-top: 28px; border-top: 1px solid #333; padding-top: 2px; width: 180px; text-align: center; display: inline-block; font-size: 8px; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <div class="title">{{ strtoupper($titulo) }}</div>
        <div class="subtitle">
            @if($tipo === 'dia') Día: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} @endif
            @if($from || $to) Rango: {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : '-' }} — {{ $to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : '-' }} @endif
            @if($area) &nbsp;|&nbsp; Área: {{ $area->title ?: $area->name }} @endif
            @if($search) &nbsp;|&nbsp; Búsqueda: "{{ $search }}" @endif
        </div>
    </div>
    <div class="header-right">
        Generado: {{ $generado->format('d/m/Y H:i') }}<br>
        Sistema SIL
    </div>
</div>

<div class="kpis">
    <span><b>Total:</b> {{ $totales['total'] }}</span>
    <span><b>Recogidos:</b> {{ $totales['recogidos'] }}</span>
    <span><b>Pendientes:</b> {{ $totales['pendientes'] }}</span>
    <span><b>Con resultado:</b> {{ $totales['realizados'] }}</span>
</div>

<table>
    <thead>
    <tr>
        <th style="width:6%">Código</th>
        <th style="width:6%">Fecha</th>
        <th style="width:18%">Paciente</th>
        <th style="width:13%">Médico</th>
        <th style="width:8%">Área</th>
        <th style="width:10%">Prestación</th>
        <th style="width:7%">Estado</th>
        <th style="width:12%">Recogido por</th>
        <th style="width:6%">C.I.</th>
        <th style="width:7%">Teléfono</th>
        <th style="width:7%">Parentesco</th>
        <th style="width:8%">Fecha/Hora Recojo</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $r)
        <tr>
            <td class="center small-text">{{ ($r->codigo ?: '') . ($r->nro_registro ?: '') ?: '-' }}</td>
            <td class="center">{{ $r->fecha_solicitud ? \Carbon\Carbon::parse($r->fecha_solicitud)->format('d/m/Y') : '-' }}</td>
            <td>{{ $r->paciente_nombre ?: '-' }}</td>
            <td>{{ $r->doctor_nombre ?: '-' }}</td>
            <td class="center">{{ $r->area_title ?: ($r->area_name ?: '-') }}</td>
            <td>{{ $r->servicio_nombre_pivot ?: ($r->servicio_nombre_catalogo ?: '-') }}</td>
            <td class="center">
                @if($r->fue_recogido)
                    <span class="badge badge-recogido">RECOGIDO</span>
                @elseif($r->realizado === 'REALIZADO')
                    <span class="badge badge-activo">CON RESULTADO</span>
                @else
                    <span class="badge badge-pendiente">PENDIENTE</span>
                @endif
            </td>
            <td>{{ $r->recogido_por_personal ?: '-' }}</td>
            <td class="center">{{ $r->ci_recogido ?: '-' }}</td>
            <td class="center">{{ $r->telefono_recogido ?: '-' }}</td>
            <td class="center">{{ $r->grado_parentesco ?: '-' }}</td>
            <td class="center">{{ $r->recogido_en_dia ? \Carbon\Carbon::parse($r->recogido_en_dia)->format('d/m/Y H:i') : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    <div class="footer-left">
        Sistema SIL &mdash; Reporte de Recogidos &mdash; Página <span class="pagenum"></span>
    </div>
    <div class="footer-right">
        <div class="firma">Responsable de Entrega</div>
    </div>
</div>

</body>
</html>
