<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter landscape; margin: 10px 12px; }
        * { box-sizing: border-box; }

        body {
            margin: 0; padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111;
        }

        .title { font-weight: 700; font-size: 12px; text-align: center; }
        .subtitle { font-size: 8px; text-align: center; color: #555; margin-top: 2px; }
        .hr { border-top: 1.6px solid #111; margin: 6px 0; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #111; padding: 2px 3px; vertical-align: top; }
        th { background: #f2f2f2; font-weight: 700; font-size: 8px; text-align: center; }

        .clip { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .center { text-align: center; }
        .small { font-size: 8px; color: #444; }
        .estado { font-weight: 700; }

        /* anchos de columnas (ajusta a tu gusto) */
        .w-fecha { width: 8%; }
        .w-estado { width: 9%; }
        .w-paciente { width: 13%; }
        .w-ci { width: 7%; }
        .w-codigo { width: 6%; }
        .w-reg { width: 9%; }
        .w-medico { width: 12%; }
        .w-prest { width: 20%; }
        .w-estab { width: 12%; }
        .w-tipo { width: 4%; }
        .w-n { width: 3%; }
        .head-blue {
            background-color: #1b62a9;
            color: #fff;
        }
    </style>
</head>
<body>

<div class="title">SOLICITUDES - ÁREA PREANALÍTICA</div>
<div class="subtitle">
    Fecha filtro: <b>{{ $fecha }}</b>
    @if($filter) &nbsp;|&nbsp; Búsqueda: <b>{{ $filter }}</b> @endif
    &nbsp;|&nbsp; Generado: <b>{{ $generado->format('d/m/Y H:i') }}</b>
    &nbsp;|&nbsp; Total: <b>{{ count($rows) }}</b>
</div>

<div class="hr"></div>

<table>
    <thead>
    <tr>
        <th class="w-fecha head-blue">F. Solicitud</th>
        <th class="w-fecha head-blue">F. Envío</th>
        <th class="w-estado head-blue">Estado</th>
        <th class="w-paciente head-blue">Paciente</th>
        <th class="w-ci head-blue">CI</th>
        <th class="w-codigo head-blue">Código</th>
        <th class="w-reg head-blue">Nro Registro</th>
        <th class="w-medico head-blue">Médico</th>
        <th class="w-prest head-blue">Prestaciones</th>
        <th class="w-estab head-blue">Establecimiento</th>
        <th class="w-tipo head-blue">Tipo</th>
        <th class="w-n head-blue">#</th>
    </tr>
    </thead>

    <tbody>
    @foreach($rows as $r)
        @php
            $paciente = $r->paciente_nombre ?? optional($r->paciente)->nombre_completo ?? '-';
            $ci = $r->paciente_ci ?? optional($r->paciente)->ci ?? '-';
            $medico = $r->doctor_nombre ?? optional($r->doctor)->name ?? '-';
            $prest = ($r->servicios ?? collect())->pluck('nombre')->filter()->implode(', ');
            $prest = $prest !== '' ? $prest : '-';
            $tipo = ($r->tipo_atencion ?? '') === 'SI' ? 'SUS' : ($r->tipo_otro ?: 'EXT');
        @endphp

        <tr>
            <td class="clip">{{ $r->fecha_creacion ? \Carbon\Carbon::parse($r->fecha_creacion)->format('d/m/Y H:i') : '-' }}</td>
            <td class="clip">{{ $r->fecha_envio_analitica ? \Carbon\Carbon::parse($r->fecha_envio_analitica)->format('d/m/Y H:i') : '-' }}</td>
            <td class="estado">{{ $r->estado ?? '-' }}</td>
            <td class="clip">{{ $paciente }}</td>
            <td class="clip center">{{ $ci }}</td>
            <td class="clip center">{{ $r->codigo ?? '-' }}</td>
            <td class="clip">{{ $r->nro_registro ?? '-' }}</td>
            <td class="clip">{{ $medico }}</td>
            <td>{{ $prest }}</td>
            <td class="clip">{{ $r->establecimiento_salud ?? '-' }}</td>
            <td class="center">{{ $tipo }}</td>
            <td class="center">{{ is_iterable($r->servicios) ? count($r->servicios) : 0 }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="small" style="margin-top: 6px;">
    Documento generado por el sistema - Área Preanalítica
</div>

</body>
</html>
