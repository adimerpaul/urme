<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
@page { size: A4 landscape; margin: 14px 18px; }
* { box-sizing: border-box; }
body { margin:0; padding:0; font-family: DejaVu Sans, sans-serif; font-size:9px; color:#212121; }

.header { background:#00695C; color:#fff; padding:10px 14px; border-radius:4px 4px 0 0; }
.header h1 { margin:0; font-size:14px; font-weight:700; }
.header p  { margin:2px 0 0; font-size:8px; opacity:.85; }

.meta { background:#E0F2F1; padding:6px 14px; font-size:8px; color:#004D40; border-bottom:2px solid #00695C; }
.meta span { margin-right:18px; }

table { width:100%; border-collapse:collapse; margin-top:8px; }
thead tr th {
    background:#00695C; color:#fff; font-size:8px; font-weight:700;
    padding:5px 6px; text-align:left; border:1px solid #004D40;
}
tbody tr td { padding:4px 6px; font-size:8px; border:1px solid #E0E0E0; vertical-align:top; }
tbody tr:nth-child(even) td { background:#E0F2F1; }

.chip { display:inline-block; padding:1px 7px; border-radius:10px; font-size:7.5px; font-weight:700; color:#fff; }
.chip-publico  { background:#2E7D32; }
.chip-privado  { background:#4527A0; }
.chip-activo   { background:#1B5E20; }
.chip-inactivo { background:#757575; }

.footer { margin-top:10px; font-size:7.5px; color:#9E9E9E; border-top:1px solid #eee; padding-top:4px; }
</style>
</head>
<body>

<div class="header">
    <h1>Establecimientos de Salud</h1>
    <p>SILL — Sistema de Información de Laboratorio Clínico</p>
</div>

<div class="meta">
    <span><b>Total:</b> {{ count($establecimientos) }}</span>
    <span><b>Generado:</b> {{ now()->format('d/m/Y H:i') }}</span>
    @if(!empty($filtros['tipo']))<span><b>Tipo:</b> {{ $filtros['tipo'] }}</span>@endif
    @if(!empty($filtros['estado']))<span><b>Estado:</b> {{ $filtros['estado'] }}</span>@endif
    @if(!empty($filtros['q']))<span><b>Búsqueda:</b> {{ $filtros['q'] }}</span>@endif
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Nivel</th>
            <th>Dirección</th>
            <th>Tel. Contacto</th>
            <th>Responsable Lab.</th>
            <th>Tel. Responsable</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($establecimientos as $i => $e)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><b>{{ $e->nombre }}</b></td>
            <td>
                <span class="chip {{ $e->tipo === 'PUBLICO' ? 'chip-publico' : 'chip-privado' }}">
                    {{ $e->tipo }}
                </span>
            </td>
            <td>{{ $e->nivel }}</td>
            <td>{{ $e->direccion }}</td>
            <td>{{ $e->telefono_contacto }}</td>
            <td>{{ $e->responsable_laboratorio }}</td>
            <td>{{ $e->telefono_responsable }}</td>
            <td>
                <span class="chip {{ $e->estado === 'ACTIVO' ? 'chip-activo' : 'chip-inactivo' }}">
                    {{ $e->estado }}
                </span>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    SILL — Establecimientos de Salud &nbsp;|&nbsp; Generado el {{ now()->format('d/m/Y H:i:s') }}
</div>

</body>
</html>
