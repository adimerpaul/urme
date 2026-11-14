<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: letter; margin: 22px 32px 92px; }
        .description { margin: 3px 0 6px; line-height: 1.4; color: #4b5563; font-size: 8.5px; }
        .description p { margin: 3px 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 9.5px; }
        .header, .patient, .results { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 3px; border-bottom: 2px solid #1D69EE; }
        .header td { vertical-align: middle; padding-bottom: 5px; }
        .laboratory-logo { width: 205px; height: auto; display: block; }
        /* CODELAB y código de la solicitud: un solo bloque, mismo estilo. */
        .document-code, .codelab { color: #0E4BB6; font-size: 15px; font-weight: bold; }
        .meta { color: #4b5563; font-size: 10px; line-height: 1.45; }
        /* El JPG del logo trae ~25px de blanco abajo: se sube el bloque para que quede justo bajo el dibujo. */
        .lab-direccion { width: 205px; margin-top: -22px; text-align: center; }
        .title { color: #0B3D91; font-size: 13px; font-weight: bold; text-align: center; margin: 5px 0 6px; letter-spacing: .3px; }
        .patient { margin-bottom: 7px; }
        .patient { table-layout: fixed; }
        .patient td { border: 1px solid #aeb9c5; padding: 3px 5px; vertical-align: top; }
        .label { font-weight: bold; color: #374151; }
        .results { margin-bottom: 4px; }
        .results th { border-top: 1.5px solid #0F54CC; border-bottom: 1.5px solid #0F54CC; color: #0B3D91; padding: 4px; text-align: left; font-size: 8.5px; }
        .results td { border-bottom: 1px solid #e5e7eb; padding: 3.5px 4px; vertical-align: top; }
        .results tr { page-break-inside: avoid; }
        .bloque-grupo { page-break-inside: avoid; break-inside: avoid; }
        /* Un área por tabla: nombre del área y títulos de columna en el thead, que se repite
           si el área sigue en otra página y no queda solo al pie. Columnas fijas: las pruebas
           anidadas (que no se parten) usan los mismos anchos y quedan alineadas. */
        .area-tabla { table-layout: fixed; }
        .area-tabla thead tr { page-break-after: avoid; }
        .results .area-titulo th { border-top: 0; border-bottom: 0; color: #0B3D91; font-size: 10.5px; text-align: center; letter-spacing: .3px; padding: 7px 0 3px; }
        .results td.contenedor { padding: 0; border-bottom: 0; }
        .results .medidas td { padding: 0; border: 0; height: 0; line-height: 0; font-size: 0; }
        .prueba-tabla { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .results tr.subtitulo { page-break-after: avoid; }
        .subtitulo td { color: #1f2937; font-weight: bold; padding-top: 5px; }
        .results .descripcion-fila td { border-bottom: 0; padding: 0 4px; }
        .descripcion-fila .description { margin: 2px 0 4px; }
        .result { font-weight: bold; }
        .reference p, .reference div, .reference ul, .reference ol, .reference blockquote { margin: 0; }
        .reference ul, .reference ol { padding-left: 12px; }
        /* Espacio para firmar + línea + textos: no se parten entre páginas. */
        .bloque-firma { page-break-inside: avoid; break-inside: avoid; }
        .espacio-firma { height: 70px; }
        .signature { width: 300px; border-top: 1px solid #374151; text-align: center; padding-top: 3px; font-size: 9px; }
        .signature-name { margin-top: 2px; font-weight: bold; color: #0B3D91; font-size: 9.5px; }
        .page-footer { position: fixed; left: 0; right: 0; bottom: -78px; height: 70px; border-top: 1px solid #c2cad3; color: #374151; }
        .page-footer table { width: 100%; height: 66px; border-collapse: collapse; }
        .page-footer td { padding: 3px 5px; vertical-align: middle; }
        .footer-message { width: 62%; color: #0E4BB6; font-size: 10.5px; font-style: italic; font-weight: bold; letter-spacing: .1px; white-space: nowrap; }
        .footer-patient { width: 25%; text-align: right; font-size: 8px; text-transform: uppercase; }
        .footer-qr { width: 13%; text-align: right; }
        .footer-qr img { width: 56px; height: 56px; }
    </style>
    @if($paraNavegador ?? false)
    <style>
        /* Impresión desde el navegador: el pie queda dentro del área imprimible y
           un tfoot vacío le reserva espacio al final de cada hoja. */
        @page { size: letter; margin: 8mm 11mm; }
        .page-footer { bottom: 0; background: #fff; }
        .print-layout { width: 100%; border-collapse: collapse; }
        .print-layout > tbody > tr > td, .print-layout > tfoot > tr > td { padding: 0; }
        .footer-space { height: 78px; }
    </style>
    @endif
@if(collect($bloques)->contains('tipo', 'perfil'))
    <style>
        /* Perfiles de exámenes: el título va en el thead de su primera área para repetirse
           si esa tabla se parte. Mismo azul de los títulos. */
        .results .perfil-titulo th { border-top: 0; border-bottom: 0; color: #0B3D91; font-size: 12px; text-align: center; letter-spacing: .5px; padding: 9px 0 1px; }
    </style>
@endif
</head>
<body>
@if($paraNavegador ?? false)
<table class="print-layout"><tfoot><tr><td class="footer-space"></td></tr></tfoot><tbody><tr><td>
@endif
<table class="header"><tr>
    <td style="width:42%">
        <img class="laboratory-logo" src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('images/logo-laboratorio-urme.jpg'))) }}" alt="Laboratorio de Diagnóstico Clínico URME">
        <div class="meta lab-direccion">
            {{ config('laboratorio.horario') }}<br>
            {{ config('laboratorio.direccion_1') }}<br>
            {{ config('laboratorio.direccion_2') }}<br>
            Cel. {{ config('laboratorio.celular') }}
        </div>
    </td>
    <td class="meta" style="width:58%;text-align:right">
        <span class="codelab">COD. CODELAB {{ config('laboratorio.codelab') }}</span><br>
        <strong class="document-code">{{ $solicitude->codigo_completo }}</strong><br>
        Fecha de solicitud: {{ $solicitude->fecha_solicitud->format('d/m/Y') }} {{ substr($solicitude->hora_solicitud, 0, 5) }}<br>
        Fecha de impresión: {{ now()->format('d/m/Y H:i') }}
    </td>
</tr></table>
<div class="title">INFORME DE LABORATORIO CLÍNICO</div>
<table class="patient">
    <tr>
        <td style="width:40%"><span class="label">PACIENTE:</span> {{ $solicitude->paciente->nombre_completo }}</td>
        <td style="width:22%"></td>
        <td style="width:38%"><span class="label">CI:</span> {{ $solicitude->paciente->ci ?: '-' }}</td>
    </tr>
    <tr>
        <td><span class="label">SEXO:</span> {{ $solicitude->paciente->sexo === 'F' ? 'FEMENINO' : ($solicitude->paciente->sexo === 'M' ? 'MASCULINO' : '-') }}</td>
        <td><span class="label">EDAD:</span> {{ $edadPaciente }}</td>
        <td><span class="label">MÉDICO:</span> {{ $solicitude->doctor?->nombre ?: 'NO ASIGNADO' }}</td>
    </tr>
    <tr>
        <td><span class="label">INSTITUCIÓN:</span> {{ $solicitude->paciente->seguro?->nombre ?: 'PARTICULAR' }}</td>
        {{-- Tipo de paciente: en blanco por ahora (pacientes.estado guarda ACTIVO/INACTIVO, no el tipo). --}}
        <td><span class="label">TIPO DE PACIENTE:</span></td>
        <td><span class="label">ESPECIALIDAD:</span> {{ $especialidades }}</td>
    </tr>
</table>
@foreach($bloques as $bloque)
@if($bloque['tipo'] === 'perfil')
    {{-- Perfil de exámenes: corto, en un bloque que no se parte; largo, cada área repite sus títulos en cada página. --}}
    <div class="{{ $bloque['corto'] ? 'bloque-grupo' : 'bloque-perfil' }}">
@foreach($bloque['areas'] as $area)
        @include('reportes.solicitud-laboratorio-area', ['area' => $area, 'perfilTitulo' => $loop->first ? $bloque['titulo'] : null])
@endforeach
@foreach($bloque['descripciones'] as $descripcion)
        <div class="description">{!! $descripcion !!}</div>
@endforeach
    </div>
@else
    @include('reportes.solicitud-laboratorio-area', ['area' => $bloque, 'perfilTitulo' => null])
@endif
@endforeach
@if($solicitude->observaciones)
    <div style="margin-top:12px"><span class="label">OBSERVACIONES:</span> {{ $solicitude->observaciones }}</div>
@endif
<div class="bloque-firma">
    <div class="espacio-firma"></div>
    <div class="signature">
        <div>RESPONSABLE DE LABORATORIO</div>
        <div class="signature-name">{{ $impresoPor->name }}</div>
    </div>
</div>
@if($paraNavegador ?? false)
</td></tr></tbody></table>
@endif
<div class="page-footer">
    <table><tr>
        <td class="footer-message">Nuestro compromiso es brindarte resultados confiables para un mejor mañana.</td>
        <td class="footer-patient">{{ $solicitude->paciente->nombre_completo }}</td>
        <td class="footer-qr"><img src="{{ $qrDataUri }}" alt="QR de verificación"></td>
    </tr></table>
</div>
</body>
</html>
