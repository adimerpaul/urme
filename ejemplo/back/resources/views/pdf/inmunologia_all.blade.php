<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: letter landscape; margin: 10px 12px; }
        .page-break{ page-break-after: always; }
    </style>
</head>
<body>

@foreach($items as $i => $row)
    @include('pdf.inmunologia_formulario', ['solicitud' => $solicitud, 'row' => $row])
    @if($i < count($items)-1)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
