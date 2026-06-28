<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
@page { size: A4 landscape; margin: 14px 18px; }
* { box-sizing: border-box; }
body { margin:0; padding:0; font-family: DejaVu Sans, sans-serif; font-size:9px; color:#212121; }

.header { background:#1565C0; color:#fff; padding:10px 14px; border-radius:4px 4px 0 0; }
.header h1 { margin:0; font-size:14px; font-weight:700; }
.header p  { margin:2px 0 0; font-size:8px; opacity:.85; }

.meta { background:#E3F2FD; padding:6px 14px; font-size:8px; color:#1A237E; border-bottom:2px solid #1565C0; }
.meta span { margin-right:18px; }

table { width:100%; border-collapse:collapse; margin-top:8px; }
thead tr th {
    background:#1565C0; color:#fff; font-size:8px; font-weight:700;
    padding:5px 6px; text-align:left; border:1px solid #0D47A1;
}
tbody tr td { padding:4px 6px; font-size:8px; border:1px solid #E0E0E0; vertical-align:top; }
tbody tr:nth-child(even) td { background:#EAF2FF; }

.chip { display:inline-block; padding:1px 7px; border-radius:10px; font-size:7.5px; font-weight:700; color:#fff; }
.chip-activo   { background:#2E7D32; }
.chip-inactivo { background:#757575; }

.footer { margin-top:10px; font-size:7.5px; color:#9E9E9E; border-top:1px solid #eee; padding-top:4px; }
</style>
</head>
<body>

<div class="header">
    <h1>Listado de Doctores</h1>
    <p>SILL — Sistema de Información de Laboratorio Clínico</p>
</div>

<div class="meta">
    <span><b>Total:</b> {{ count($doctores) }}</span>
    <span><b>Generado:</b> {{ now()->format('d/m/Y H:i') }}</span>
    @if(!empty($filtros['estado']))<span><b>Estado:</b> {{ $filtros['estado'] }}</span>@endif
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Especialidad</th>
            <th>CI</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Registro</th>
            <th>Establecimiento</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($doctores as $i => $d)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><b>{{ $d->nombre }}</b></td>
            <td>{{ $d->especialidad }}</td>
            <td>{{ $d->ci }}</td>
            <td>{{ $d->telefono }}</td>
            <td>{{ $d->email }}</td>
            <td>{{ $d->registro }}</td>
            <td>{{ $d->establecimiento?->nombre ?? '—' }}</td>
            <td>
                <span class="chip {{ $d->estado === 'ACTIVO' ? 'chip-activo' : 'chip-inactivo' }}">
                    {{ $d->estado }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    SILL — Doctores &nbsp;|&nbsp; Página generada el {{ now()->format('d/m/Y H:i:s') }}
</div>

</body>
</html>
