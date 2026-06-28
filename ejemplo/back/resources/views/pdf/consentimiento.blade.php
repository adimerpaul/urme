<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento informado</title>

    <style>
        @page { size: legal portrait; margin: 5mm 6mm; }

        * { box-sizing: border-box; }

        body{
            margin:0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.4px;
            line-height: 1;
            color:#000;
        }

        table{
            width:100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td, th{
            border:0.6px solid #000;
            padding:0.4px 0.8px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .no-border td{ border:none; padding:0; }

        .head-title{
            text-align:center;
            font-weight:700;
            font-size: 9.4px;
            line-height: 1;
        }

        .head-sub{
            text-align:center;
            font-weight:700;
            font-size: 7.6px;
            margin-top: 0.5px;
        }

        .mini{
            font-size: 6.8px;
            line-height: 1;
        }

        .logo{ height: 36px; }

        .label{
            font-weight:700;
            letter-spacing: 0.1px;
        }

        .box{
            display:inline-block;
            width: 7px;
            height: 7px;
            border: 0.8px solid #000;
            vertical-align: middle;
            margin: 0 1px;
            text-align:center;
            line-height: 6px;
            font-size: 7px;
        }

        .center{ text-align:center; }
        .right{ text-align:right; }
        .justify{ text-align:justify; }
        .section-gap{ margin-top:1px; }
        .tight-gap{ margin-bottom:1px; }
        .firma-space{ height:22px; }
        .sello-space{ height:10px; }
    </style>
</head>

<body>
@php
    $genero = strtoupper((string) ($c->genero ?? ''));
    $acepta = strtoupper((string) ($c->tipo ?? '')) === 'ACEPTA';
    $rechaza = strtoupper((string) ($c->tipo ?? '')) === 'RECHAZA';

    function formatFechaNacimientodmY($fecha) {
        if (!$fecha) return '';
        try {
            $date = new DateTime($fecha);
            return $date->format('d/m/Y');
        } catch (Exception $e) {
            return $fecha;
        }
    }

    function formatHora($hora) {
        if (!$hora) return '';
        return substr((string) $hora, 0, 5);
    }
@endphp

<table class="no-border tight-gap">
    <tr>
        <td style="width:16%;">
            <img src="{{ public_path('img/logo-hospital.png') }}" class="logo">
        </td>

        <td style="width:68%; text-align:center;">
            <div class="head-title">FORMATO FORMULARIO DE CONSENTIMIENTO INFORMADO</div>
            <div class="head-title">HOSPITAL GENERAL SAN JUAN DE DIOS</div>
            <div class="head-sub">SERVICIO DE LABORATORIO DE ANALISIS CLINICO MICROBIOLOGICO</div>
            <div class="mini">
                DIRECCION: CALLE SAN FELIPE ENTRE 6 DE OCTUBRE Y TARIJA<br>
                REGISTRO CONALAB: 001BBB REGISTRO CODELAB: 000004BB
            </div>
        </td>

        <td style="width:16%;" class="right">
            <img src="{{ public_path('img/logo-labo.png') }}" class="logo">
        </td>
    </tr>
</table>

<table class="tight-gap">
    <tr>
        <td class="mini">
            (LLENADO DEL MISMO DEBERA SER CON LETRA IMPRENTA CLARA Y LEGIBLE, BOLIGRAFO AZUL,
            Y MARCANDO CON X LO QUE CORRESPONDA; EN FORMA COMPLETA)
        </td>
    </tr>
</table>

<table>
    <tr>
        <td style="width:62%;">
            <span class="label">FECHA RECEPCION:</span> {{ formatFechaNacimientodmY($c->fecha_recepcion) }}
        </td>
        <td style="width:38%;">
            <span class="label">HORA DE RECEP:</span> {{ $c->hora_recepcion }}
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <span class="label">NOMBRE COMPLETO PACIENTE:</span> {{ $c->nombre_completo }}
        </td>
    </tr>

    <tr>
        <td style="width:62%;">
            <span class="label">FECHA DE NAC:</span> {{ formatFechaNacimientodmY($c->fecha_nac) }}
        </td>
        <td style="width:38%;">
            <span class="label">GENERO:</span>
            F <span class="box">{{ $genero === 'F' ? 'X' : '' }}</span>
            M <span class="box">{{ $genero === 'M' ? 'X' : '' }}</span>
        </td>
    </tr>

    <tr>
        <td style="width:62%;">
            <span class="label">EDAD:</span> {{ $c->edad }}
        </td>
        <td style="width:38%;">
            <span class="label">CI:</span> {{ $c->ci }}
        </td>
    </tr>

    <tr>
        <td style="width:62%;">
            <span class="label">FECHA SOLICITUD:</span> {{ $c->fecha_solicitud }}
        </td>
        <td style="width:38%;">
            <span class="label">TELEFONO:</span> {{ $c->telefono }}
        </td>
    </tr>

    <tr>
        <td style="width:62%;">
            <span class="label">DISCAP:</span>
            SI <span class="box">{{ !empty($c->discapacidad) ? 'X' : '' }}</span>
            NO <span class="box">{{ empty($c->discapacidad) ? 'X' : '' }}</span>
            <span class="label" style="margin-left:6px;">CUAL?</span> {{ $c->discapacidad_cual }}
        </td>
        <td style="width:38%;">
            <span class="label">EMB</span>
            SI <span class="box">{{ !empty($c->embarazo) ? 'X' : '' }}</span>
            NO <span class="box">{{ empty($c->embarazo) ? 'X' : '' }}</span>
            <span class="label" style="margin-left:6px;">FUM:</span> {{ $c->fum }}
        </td>
    </tr>

    <tr>
        <td style="width:62%;">
            <span class="label">MEDICAMENTO:</span>
            SI <span class="box">{{ !empty($c->medicamento) ? 'X' : '' }}</span>
            NO <span class="box">{{ empty($c->medicamento) ? 'X' : '' }}</span>
        </td>
        <td style="width:38%;">
            <span class="label">TRATAMIENTO:</span> {{ $c->tratamiento }}
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <span class="label">CONDICION:</span>
            BASAL <span class="box">{{ ($c->condicion ?? '') === 'BASAL' ? 'X' : '' }}</span>
            AYUN PROL <span class="box">{{ ($c->condicion ?? '') === 'AYUNO PROL' ? 'X' : '' }}</span>
            POST PRANDIAL <span class="box">{{ ($c->condicion ?? '') === 'POST PRANDIAL' ? 'X' : '' }}</span>
        </td>
    </tr>
</table>

<table class="section-gap">
    <tr>
        <td class="center" style="font-weight:700;">CONSENTIMIENTO INFORMADO</td>
    </tr>
</table>

<table>
    <tr>
        <td class="justify">
            <span class="label">Yo:</span> {{ $c->declarante_nombre }}
            <span class="label">en mi condicion de</span>
            {{ ($c->declarante_condicion ?? '') === 'Otros' ? ($c->declarante_condicion_otro ?? '') : ($c->declarante_condicion ?? '') }}
            <br>

            <span class="label">ACEPTO</span> <span class="box">{{ $acepta ? 'X' : '' }}</span>
            <span class="label">RECHAZO</span> <span class="box">{{ $rechaza ? 'X' : '' }}</span>
            la toma de muestra (previa orientacion), a requerimiento para el (los) examen(es) a realizarse.
            Y acepto total responsabilidad de los inconvenientes o consecuencias que surjan al no acatar dichas indicaciones y recomendaciones.
        </td>
    </tr>

    <tr>
        <td class="center">
            <div class="firma-space"></div>
            <span class="label">FIRMA / HUELLA:</span>
            <span style="display:inline-block; width:65%; border-bottom:0.8px solid #000; height:8px;"></span>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span class="label">FECHA:</span>
            {{ $c->fecha_consentimiento }}
        </td>
    </tr>
</table>

<table class="section-gap">
    <tr>
        <td colspan="2">
            <span class="label">TIPO DE MUESTRA:</span>
            LIQUIDOS <span class="box">{{ !empty($c->m_liquidos) ? 'X' : '' }}</span>
            &nbsp;&nbsp;
            ESPUTO <span class="box">{{ !empty($c->m_esputo) ? 'X' : '' }}</span>
            <br>
            SECRECIONES <span class="box">{{ !empty($c->m_secreciones) ? 'X' : '' }}</span>
            &nbsp;&nbsp;
            ORINA <span class="box">{{ !empty($c->m_orina) ? 'X' : '' }}</span>
            <span class="label">HR RECOLECCION:</span> {{ formatHora($c->hr_recoleccion_orina) }}
            &nbsp;&nbsp;
            HECES <span class="box">{{ !empty($c->m_heces) ? 'X' : '' }}</span>
            <span class="label">HR RECOLECCION:</span> {{ formatHora($c->hr_recoleccion_heces) }}
        </td>
    </tr>

    <tr>
        <td style="width:50%">
            <span class="label">OBSERVACIONES:</span> {{ $c->observaciones }}
        </td>
        <td style="width:50%">
            <span class="label">HR RECOLECCIÓN:</span> {{ formatHora($c->hr_recoleccion) }}
        </td>
    </tr>

    <tr>
        <td colspan="2">
            <span class="label">RESPONSABLE TOMA DE MUESTRA</span>
            <div class="sello-space"></div>
            <span class="mini">(SELLO)</span>
        </td>
    </tr>
</table>

</body>
</html>
