<?php

// Datos del laboratorio que se imprimen en el encabezado del informe (reportes/solicitud-laboratorio).
return [

    'codelab' => env('LAB_CODELAB', '000043'),

    'horario' => env('LAB_HORARIO', '24 horas'),

    'direccion_1' => env('LAB_DIRECCION_1', 'Calle Cochabamba'),

    'direccion_2' => env('LAB_DIRECCION_2', 'entre Soria Galvarro y 6 de Octubre'),

    'celular' => env('LAB_CELULAR', '63944238'),

    // Perfiles de exámenes (docs/ANALISIS_PERFILES_FASE0.md). Apagado, el catálogo
    // no ofrece perfiles y sus endpoints responden 404.
    'perfiles_habilitado' => filter_var(env('LAB_PERFILES_HABILITADO', false), FILTER_VALIDATE_BOOLEAN),

];
