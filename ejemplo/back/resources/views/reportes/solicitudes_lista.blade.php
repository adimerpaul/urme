<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter landscape; margin: 12px 14px; }
        * { box-sizing: border-box; }

        body {
            margin: 0; padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #111;
        }

        .header-block { margin-bottom: 6px; }
        .title {
            font-weight: 700;
            font-size: 12px;
            text-align: center;
            color: #1565C0;
            letter-spacing: 0.5px;
        }
        .subtitle {
            font-size: 7.5px;
            text-align: center;
            color: #555;
            margin-top: 3px;
        }
        .hr { border: none; border-top: 1.8px solid #1565C0; margin: 5px 0 6px; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }

        thead tr.head-row th {
            background-color: #1976D2;
            color: #fff;
            font-weight: 700;
            font-size: 7.5px;
            text-align: center;
            padding: 4px 3px;
            border: 1px solid #1565C0;
        }

        tbody tr td {
            border: 1px solid #ccc;
            padding: 3px 3px;
            vertical-align: top;
            font-size: 8px;
        }

        tbody tr:nth-child(even) td { background-color: #F0F4FF; }
        tbody tr:nth-child(odd)  td { background-color: #FFFFFF; }

        .tfoot-row td {
            background-color: #E3F0FF !important;
            font-weight: 700;
            font-size: 8px;
            border: 1px solid #aac;
            padding: 3px;
        }

        .center { text-align: center; }
        .clip   { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* anchos */
        .w-n      { width: 3%;  }
        .w-reg    { width: 7%;  }
        .w-cod    { width: 7%;  }
        .w-fecha  { width: 7%;  }
        .w-pac    { width: 17%; }
        .w-doc    { width: 14%; }
        .w-tipo   { width: 8%;  }
        .w-srv    { width: 21%; }
        .w-est    { width: 7%;  }
        .w-obs    { width: 9%;  }

        .chip {
            display: inline-block;
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 7px;
            font-weight: 700;
            color: #fff;
        }
        .chip-finalizado  { background-color: #388E3C; }
        .chip-atendiendo  { background-color: #1976D2; }
        .chip-creado      { background-color: #757575; }
        .chip-rechazada   { background-color: #C62828; }
        .chip-default     { background-color: #546E7A; }
    </style>
</head>
<body>

<div class="header-block">
    <div class="title">LISTADO DE SOLICITUDES</div>
    <div class="subtitle">
        Período: <b>{{ $from }}</b> al <b>{{ $to }}</b>
        &nbsp;|&nbsp; Total: <b>{{ count($rows) }}</b>
        &nbsp;|&nbsp; Generado: <b>{{ $generado->format('d/m/Y H:i') }}</b>
    </div>
</div>

<hr class="hr">

<table>
    <thead>
        <tr class="head-row">
            <th class="w-n">N°</th>
            <th class="w-reg">Nro Reg.</th>
            <th class="w-cod">Cód. Sol.</th>
            <th class="w-fecha">Fecha</th>
            <th class="w-pac">Paciente</th>
            <th class="w-doc">Doctor</th>
            <th class="w-tipo">Tipo Atención</th>
            <th class="w-srv">Servicios</th>
            <th class="w-est">Estado</th>
            <th class="w-obs">Observación</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
            @php
                $servicios = $row->servicios->pluck('nombre')->implode(', ');
                $fecha     = $row->fecha_creacion ? \Illuminate\Support\Str::substr($row->fecha_creacion, 0, 10) : '';
                $estado    = strtolower($row->estado ?? '');
                $chipClass = match(true) {
                    str_contains($estado, 'finalizado') || str_contains($estado, 'analizado') => 'chip-finalizado',
                    str_contains($estado, 'atendiendo')  => 'chip-atendiendo',
                    str_contains($estado, 'creado')      => 'chip-creado',
                    str_contains($estado, 'rechazada')   => 'chip-rechazada',
                    default                              => 'chip-default',
                };
            @endphp
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="center clip">{{ $row->nro_registro ?? '' }}</td>
                <td class="center clip">{{ $row->codigo_solicitud ?? $row->codigo ?? '' }}</td>
                <td class="center">{{ $fecha }}</td>
                <td class="clip">{{ $row->paciente_nombre ?? '' }}</td>
                <td class="clip">{{ $row->doctor_nombre ?? '' }}</td>
                <td class="center clip">{{ $row->tipo_atencion ?? '' }}</td>
                <td class="clip" style="font-size:7px;">{{ $servicios }}</td>
                <td class="center">
                    <span class="chip {{ $chipClass }}">{{ strtoupper($row->estado ?? '') }}</span>
                </td>
                <td class="clip" style="font-size:7px; color:#444;">{{ $row->muestra_observacion ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="center" style="padding:10px; color:#888;">
                    No se encontraron solicitudes para el período seleccionado.
                </td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="tfoot-row">
            <td colspan="2" class="center">TOTAL</td>
            <td colspan="8"><b>{{ count($rows) }}</b> solicitudes</td>
        </tr>
    </tfoot>
</table>

</body>
</html>
