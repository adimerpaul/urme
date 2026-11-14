{{--
    Tabla de un área del informe de laboratorio (la usa solicitud-laboratorio.blade.php).
    $area: ['nombre', 'pruebas' => [['filas', 'junta'], ...]]; $perfilTitulo: título del perfil o null.
    El thead (título del perfil, nombre del área y títulos de columna) se repite si el área
    sigue en otra página y no queda solo al pie. Una prueba «junta» va en una tabla anidada
    dentro de una sola fila para que no se parta; las columnas son las mismas del thead.
--}}
{{-- dompdf toma los anchos de la primera fila (ignora <col>): cada tabla empieza con una fila de medidas sin alto. --}}
@php($columnas = '<tr class="medidas"><td style="width:25%"></td><td style="width:15%"></td><td style="width:26%"></td><td style="width:18%"></td><td style="width:16%"></td></tr>')
<table class="results area-tabla">
    <thead>
        {!! $columnas !!}
@if($perfilTitulo)
        <tr class="perfil-titulo"><th colspan="5">{{ mb_strtoupper($perfilTitulo) }}</th></tr>
@endif
        <tr class="area-titulo"><th colspan="5">{{ mb_strtoupper($area['nombre']) }}</th></tr>
        <tr class="columnas">
            <th>ANÁLISIS</th>
            <th>RESULTADO</th>
            <th>VALORES DE REFERENCIA</th>
            <th>MÉTODO</th>
            <th>MUESTRA</th>
        </tr>
    </thead>
    <tbody>
@foreach($area['pruebas'] as $prueba)
@if($prueba['junta'])
        <tr><td colspan="5" class="contenedor"><table class="prueba-tabla">
            {!! $columnas !!}
@endif
@foreach($prueba['filas'] as $fila)
@if($fila['tipo'] === 'subtitulo')
            <tr class="subtitulo"><td colspan="5">{{ $fila['nombre'] }}</td></tr>
@elseif($fila['tipo'] === 'descripcion')
            <tr class="descripcion-fila"><td colspan="5"><div class="description">{!! $fila['html'] !!}</div></td></tr>
@else
@php($resultado = $fila['resultado'])
            <tr>
                <td>{{ $fila['nombre'] }}</td>
                <td class="result">{{ filled($resultado->valor) ? $resultado->valor : '-' }} {{ $resultado->unidad }}</td>
                <td class="reference">{!! filled($resultado->rango_referencia) ? $resultado->rango_referencia_html : '-' !!}</td>
                <td>{{ $resultado->metodo ?: '-' }}</td>
                <td>{{ $resultado->muestra ?: '-' }}</td>
            </tr>
@endif
@endforeach
@if($prueba['junta'])
        </table></td></tr>
@endif
@endforeach
    </tbody>
</table>
