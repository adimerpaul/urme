<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { size: letter portrait; margin: 12mm 14mm 14mm 14mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 8px; color: #111; line-height: 1.4; }

    .center { text-align: center; }
    .bold   { font-weight: bold; }

    /* ── Cabecera ── */
    .header { text-align: center; margin-bottom: 4px; }
    .header .inst   { font-size: 9px; font-weight: bold; text-transform: uppercase; }
    .header .sub    { font-size: 7.5px; text-transform: uppercase; }
    .header .titulo { font-size: 11px; font-weight: bold; text-transform: uppercase; margin-top: 5px; letter-spacing: 0.3px; }
    .header .area   { font-size: 9.5px; font-weight: bold; text-transform: uppercase; }

    /* ── Meta datos ── */
    .meta { width: 100%; border-collapse: collapse; margin: 6px 0 4px 0; font-size: 8.5px; }
    .meta td { padding: 1px 4px; }
    .meta .lbl { font-weight: bold; }

    /* ── Tabla principal ── */
    table.main { width: 100%; border-collapse: collapse; margin-top: 4px; }

    .tbl-title {
        background: #555;
        color: #fff;
        font-weight: bold;
        font-size: 8px;
        text-align: center;
        padding: 3px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    thead th {
        background: #555;
        color: #fff;
        font-size: 7px;
        font-weight: bold;
        text-align: center;
        padding: 3px 2px;
        border: 1px solid #888;
        vertical-align: middle;
        text-transform: uppercase;
        line-height: 1.2;
    }

    tbody td {
        border: 1px solid #ccc;
        padding: 2px 4px;
        font-size: 7.5px;
        vertical-align: middle;
    }

    tbody tr:nth-child(even) td { background: #f7f7f7; }

    .td-grupo {
        background: #555 !important;
        color: #fff;
        font-weight: bold;
        font-size: 7px;
        text-align: center;
        vertical-align: middle;
        text-transform: uppercase;
        writing-mode: horizontal-tb;
        width: 13%;
    }

    .td-label { width: 33%; }
    .td-ref   { width: 24%; text-align: center; }
    .td-cnt   { width: 15%; text-align: center; font-weight: bold; font-size: 8px; }
</style>
</head>
<body>

{{-- ══ CABECERA ══ --}}
<div class="header">
    <div class="inst">Hospital General San Juan de Dios</div>
    <div class="sub">Servicio de Laboratorio de Análisis Clínico Microbiológico</div>
    <div class="titulo">Informe de Producción y Vigilancia de ENTs</div>
    <div class="area">Área Química Sanguínea</div>
</div>

{{-- ══ META DATOS ══ --}}
<table class="meta">
    <tr>
        <td class="lbl">MES:</td>
        <td class="bold">{{ $mes }}</td>
        <td class="lbl">GESTIÓN:</td>
        <td class="bold">{{ $gestion }}</td>
    </tr>
    <tr>
        <td class="lbl">TOTAL PACIENTES ATENDIDOS SUS:</td>
        <td class="bold">{{ $totalSus }}</td>
        <td class="lbl">TOTAL</td>
        <td class="bold">{{ $totalGeneral }}</td>
    </tr>
    <tr>
        <td class="lbl">TOTAL PACIENTES ATENDIDOS EXTERNOS:</td>
        <td class="bold">{{ $totalExternos }}</td>
    </tr>
</table>

{{-- ══ TABLA PRINCIPAL ══ --}}
<table class="main">
    <thead>
        <tr>
            <th colspan="4" class="tbl-title">PRUEBAS PARA ENFERMEDADES NO TRANSMISIBLES</th>
        </tr>
        <tr>
            <th colspan="2">PRUEBAS DE DIAGNÓSTICO</th>
            <th>VALORES DE REFERENCIA<br>UTILIZADOS</th>
            <th>POR ENCIMA DE LOS<br>VALORES DE REFERENCIA</th>
            <th>POR DEBAJO DE LOS<br>VALORES DE REFERENCIA</th>
        </tr>
    </thead>
    <tbody>
    @foreach($grupos as $grupoLabel => $items)
        @foreach($items as $i => $item)
        <tr>
            @if($i === 0)
            <td class="td-grupo" rowspan="{{ count($items) }}">{{ $grupoLabel }}</td>
            @endif
            <td class="td-label">{{ $item['label'] }}</td>
            <td class="td-ref">{{ $item['ref'] }}</td>
            <td class="td-cnt">{{ $item['cnt'][0] }}</td>
            <td class="td-cnt">{{ $item['cnt'][1] }}</td>
        </tr>
        @endforeach
    @endforeach
    </tbody>
</table>

</body>
</html>
