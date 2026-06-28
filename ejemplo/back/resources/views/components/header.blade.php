<style>
    /*@page { size: letter landscape; margin: 10px 12px; }*/
    * { box-sizing: border-box; }

    body{
        margin:0; padding:0;
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color:#111;
        line-height: 1;
    }

    .title { font-weight:700; font-size: 10.2px; text-align:center; }
    .subtitle { font-size: 8px; text-align:center; margin-top: 1px; }
    .muted { color:#555; }
    .small { font-size: 7.6px; }
    .center { text-align:center; }
    .right { text-align:right; }

    .hr { border-top: 1.8px solid #111; margin: 2px 0; }

    .section-title{
        margin-top: 2px;
        margin-bottom: 1px;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .25px;
    }

    table{ width:100%; border-collapse: collapse; table-layout: fixed; }
    /*th, td{ border:1px solid #111; padding: 1px 2px; vertical-align: middle; }*/
    th{ background:#f2f2f2; font-weight:700; font-size: 7px; }
    .no-border td, .no-border th{ border:none; padding:0; }

    .form-grid td{
        border:none;
        padding: 2px 3px 2px 0;
        vertical-align: bottom;
        font-size: 7.6px;
    }
    .label{ font-weight:700; }
    .line{
        border-bottom: 1px solid #111;
        height: 12px;
        padding: 0 3px;
        font-size: 7.7px;
    }

    img{ max-width: 100%; }
    .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }
    .out-range{ color: #c10015; font-weight: 700; }

    /* ✅ layout estable en DomPDF */
    .sheet-table { width:100%; border-collapse: collapse; }
    .sheet-table td { border:none; vertical-align: top; }
    .half-cell { width:50%; padding: 0 6px; }

    /* evita cortes raros */
    .block { page-break-inside: avoid; }
</style>
<table class="no-border">
    <tr>
        <td style="width:15%;">
            <img src="{{ public_path('img/logo-hospital.png') }}" style="width:58px;">
        </td>
        <td>
            <div class="title">HOSPITAL GENERAL SAN JUAN DE DIOS ORURO BLOQUE CENTRAL</div>
            <div class="subtitle muted">LABORATORIO DE ANÁLISIS CLÍNICO - MICROBIOLÓGICO</div>
            <div class="subtitle muted small">Dirección: San Felipe entre 6 de Octubre y Tarija</div>
            <div class="subtitle muted small">REGISTRO CONALAB: 001 &nbsp;&nbsp; REGISTRO CODELAB: 000004</div>
        </td>
        <td style="width:15%;" class="right">
            <img src="{{ public_path('img/logo-labo.png') }}" style="width:58px;">
        </td>
    </tr>
</table>

<div class="hr"></div>
<table class="form-grid" style="margin-top:2px;">
    <tr>
        <td style="width:15%"><span class="label" >CÓDIGO:</span></td>
        <td style="width:10%"><div class="line clip" style="font-size: 22px">{{ $solicitud->codigo ?? $solicitud->id }}</div></td>
        <td style="width:10%"><span class="label">ATENCION:</span></td>
        <td style="width:15%"><div class="line clip">{{ ($solicitud->tipo_atencion ?? '') === 'SI' ? 'SUS' : 'EXT' }}</div></td>
        <td colspan="2" style="width:20%"><span class="label">NRO. REGISTRO:</span></td>
        <td colspan="2" style="width:30%"><div class="line clip">{{ $solicitud->nro_registro ?? '-' }}</div></td>
    </tr>

    <tr>
        <td><span class="label">PACIENTE:</span></td>
        <td colspan="3"><div class="line clip">{{ $solicitud->paciente_nombre ?? '-' }}</div></td>
        <td><span class="label">EDAD:</span></td>
        <td><div class="line clip">{{ $solicitud->paciente_edad ?? '-' }}</div></td>
        <td><span class="label">SEXO:</span></td>
        <td><div class="line clip">{{ $solicitud->paciente_genero ?? '-' }}</div></td>
    </tr>

    <tr>
        <td><span class="label">MEDICO SOL.:</span></td>
        <td colspan="3"><div class="line clip">{{ $solicitud->doctor_nombre ?? '-' }}</div></td>
        <td><span class="label">DX:</span></td>
        <td colspan="3"><div class="line clip">{{ $solicitud->diagnostico_select ?? '-' }}</div></td>
    </tr>

    <tr>
        <td colspan="2"><span class="label">CODIGO MUESTRA:</span></td>
        <td colspan="2" class="line clip">
            {{ ($solicitud->codigo ?? '-') . '-' . ($solicitud->nro_registro ?? '-') }}
        </td>
        <td><span class="label">SERVICIO/SALA/CAMA:</span></td>
        <td colspan="2"><div class="line clip">{{ $solicitud->sala ?? '-' }} / {{ $solicitud->cama ?? '-' }}</div></td>
    </tr>

    <tr>
        <td><span class="label">EST. DE SALUD:</span></td>
        <td colspan="3"><div class="line clip">{{ $solicitud->establecimiento_salud ?? '-' }}</div></td>
        <td colspan="2"><span class="label">FECHA DE RESULTADO:</span></td>
        <td colspan="2"><div class="line clip">{{ $solicitud->fecha_finalizacion ?? '-' }}</div></td>
    </tr>
</table>
