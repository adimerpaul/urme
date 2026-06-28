<style>
    table{ width:100%; border-collapse: collapse; table-layout: fixed; }
    th{ background:#f2f2f2; font-weight:700; font-size: 7px; }
    .no-border td, .no-border th{ border:none; padding:0; }

    .form-grid td{
        border:none;
        padding: 2px 3px 2px 0;
        vertical-align: bottom;
        font-size: 10px;
    }
    .label{ font-weight:700; }
    .line{
        height: 12px;
        padding: 0 3px;
        font-size: 10px;
    }

    img{ max-width: 100%; }
    .clip{ overflow:hidden; text-overflow: ellipsis; white-space: nowrap; }
    .out-range{ color: #c10015; font-weight: 700; }

    .sheet-table { width:100%; border-collapse: collapse; }
    .sheet-table td { border:none; vertical-align: top; }
    .half-cell { width:50%; padding: 0 6px; }

    .block { page-break-inside: avoid; }

    /* Título laboratorio */
    .lab-title {
        font-weight: 700;
        font-size: 13px;
        text-align: center;
        text-transform: uppercase;
        line-height: 1.3;
    }
    .lab-subtitle {
        font-size: 10px;
        text-align: center;
        color: #444;
        margin-top: 1px;
    }
    .lab-small {
        font-size: 8px;
        text-align: center;
        color: #666;
    }
    .hr { border-top: 1.5px solid #111; margin: 3px 0; }

    /* Nombre paciente grande */
    .paciente-nombre {
        font-size: 14px;
        font-weight: 700;
    }
</style>

<table class="no-border" style="margin-bottom:3px;height: 60px">
    <tr>
        <td style="width:13%;">
{{--            <img src="{{ public_path('img/logo-hospital.png') }}" style="width:54px;">--}}
        </td>
        <td>
{{--            <div class="lab-title">HOSPITAL GENERAL SAN JUAN DE DIOS ORURO BLOQUE CENTRAL</div>--}}
{{--            <div class="lab-subtitle">LABORATORIO DE ANÁLISIS CLÍNICO - MICROBIOLÓGICO</div>--}}
{{--            <div class="lab-small">Dirección: San Felipe entre 6 de Octubre y Tarija</div>--}}
{{--            <div class="lab-small">REGISTRO CONALAB: 001 &nbsp;&nbsp; REGISTRO CODELAB: 000004</div>--}}
        </td>
        <td style="width:13%;" style="text-align:right;">
{{--            <img src="{{ public_path('img/logo-labo.png') }}" style="width:54px;">--}}
        </td>
    </tr>
</table>

<div class="hr"></div>

<table class="form-grid">
    <tr>
        <td style="width:15%"><span class="label">CÓDIGO:</span></td>
        <td style="width:15%"><div style="font-size: 28px; font-weight:700; line-height:1;">{{ $solicitud->codigo ?? $solicitud->id }}</div></td>
        <td style="width:10%"><span class="label">ATENCION:</span></td>
        <td style="width:15%"><div class="clip">{{ ($solicitud->tipo_atencion ?? '') === 'SI' ? 'SUS' : 'EXT' }}</div></td>
        <td colspan="2" style="width:20%"><span class="label">NRO. REGISTRO:</span></td>
        <td colspan="2" style="width:25%"><div class="clip">{{ $solicitud->nro_registro ?? '-' }}</div></td>
    </tr>

    <tr>
        <td><span class="label">PACIENTE:</span></td>
        <td colspan="3"><div class="clip paciente-nombre">{{ $solicitud->paciente_nombre ?? '-' }}</div></td>
        <td><span class="label">EDAD:</span></td>
        <td><div class="clip">{{ $solicitud->paciente_edad ?? '-' }}</div></td>
        <td><span class="label">SEXO:</span></td>
        <td><div class="clip">{{ $solicitud->paciente_genero ?? '-' }}</div></td>
    </tr>

    <tr>
        <td><span class="label">MEDICO SOL.:</span></td>
        <td colspan="3"><div class="clip">{{ $solicitud->doctor_nombre ?? '-' }}</div></td>
        <td><span class="label">DX:</span></td>
        <td colspan="3"><div class="clip">{{ $solicitud->diagnostico_select ?? '-' }}</div></td>
    </tr>

    <tr>
        <td colspan="2"><span class="label">CODIGO MUESTRA:</span></td>
        <td colspan="2" class="clip">
            {{ ($solicitud->codigo ?? '-') . '-' . ($solicitud->nro_registro ?? '-') }}
        </td>
        <td colspan="2"><span class="label">SERVICIO/SALA/CAMA:</span></td>
        <td><div class="clip">{{ $solicitud->sala ?? '-' }} / {{ $solicitud->cama ?? '-' }}</div></td>
    </tr>

    <tr>
        <td><span class="label">EST. DE SALUD:</span></td>
        <td colspan="3"><div class="clip">{{ $solicitud->establecimiento_salud == 'Hospital General' ? 'HGSJDD BLOQUE CENTRAL' : ($solicitud->establecimiento_salud ?? '-') }}</div></td>
        <td colspan="2"><span class="label">FECHA DE RESULTADO:</span></td>
        <td colspan="2"><div class="clip">{{ $fecha_solicitud ?? '-' }}</div></td>
    </tr>
</table>
