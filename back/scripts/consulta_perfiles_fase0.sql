-- SOLO LECTURA. Diagnóstico para "perfiles de exámenes" (docs/ANALISIS_PERFILES_FASE0.md, §8).
-- Solo SELECT sobre el catálogo y conteos: no devuelve datos de pacientes.
-- Se puede ejecutar completo en phpMyAdmin o en el cliente mysql; cada consulta
-- es independiente y empieza con su número para cruzarla con el documento.
-- "Laboratorio" = producto cuyo tipo tiene es_laboratorio = 1.

-- 1. Conteos generales (vivos = sin soft delete).
SELECT 'productos_laboratorio_activos' AS dato, COUNT(*) AS cantidad
FROM productos p JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE p.deleted_at IS NULL AND tp.es_laboratorio = 1
UNION ALL
SELECT 'productos_laboratorio_sin_datos', COUNT(*)
FROM productos p JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE p.deleted_at IS NULL AND tp.es_laboratorio = 1
  AND NOT EXISTS (SELECT 1 FROM producto_laboratorio_datos d WHERE d.producto_id = p.id AND d.deleted_at IS NULL)
UNION ALL
SELECT 'productos_laboratorio_con_grupo_reporte', COUNT(*)
FROM productos p JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE p.deleted_at IS NULL AND tp.es_laboratorio = 1 AND TRIM(COALESCE(p.grupo_reporte, '')) <> ''
UNION ALL
SELECT 'productos_con_reactivos_configurados', COUNT(DISTINCT s.producto_id)
FROM servicio_laboratorio_reactivos s WHERE s.deleted_at IS NULL
UNION ALL
SELECT 'solicitudes_vivas', COUNT(*) FROM solicitudes WHERE deleted_at IS NULL
UNION ALL
SELECT 'solicitudes_eliminadas', COUNT(*) FROM solicitudes WHERE deleted_at IS NOT NULL
UNION ALL
SELECT 'solicitud_laboratorio_items_vivos', COUNT(*) FROM solicitud_laboratorio_items WHERE deleted_at IS NULL
UNION ALL
SELECT 'ventas_vivas', COUNT(*) FROM ventas WHERE deleted_at IS NULL
UNION ALL
SELECT 'ventas_con_algun_examen', COUNT(DISTINCT vd.venta_id)
FROM venta_detalles vd
JOIN productos p ON p.id = vd.producto_id
JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE tp.es_laboratorio = 1 AND vd.deleted_at IS NULL;

-- 2. Solicitudes por estado (hoy ningún endpoint cambia el estado: se espera solo CREADO).
SELECT estado, COUNT(*) AS cantidad
FROM solicitudes WHERE deleted_at IS NULL
GROUP BY estado ORDER BY estado;

-- 3. Exámenes repetidos.
SELECT 'ventas_con_el_mismo_examen_en_2_o_mas_lineas' AS dato, COUNT(*) AS cantidad
FROM (
    SELECT vd.venta_id
    FROM venta_detalles vd
    JOIN productos p ON p.id = vd.producto_id
    JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
    WHERE tp.es_laboratorio = 1 AND vd.deleted_at IS NULL
    GROUP BY vd.venta_id, vd.producto_id HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'lineas_de_examen_con_cantidad_mayor_a_1', COUNT(*)
FROM venta_detalles vd
JOIN productos p ON p.id = vd.producto_id
JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE tp.es_laboratorio = 1 AND vd.deleted_at IS NULL AND vd.cantidad > 1
UNION ALL
SELECT 'solicitudes_con_el_mismo_producto_repetido', COUNT(*)
FROM (
    SELECT solicitude_id
    FROM solicitud_laboratorio_items
    WHERE deleted_at IS NULL
    GROUP BY solicitude_id, producto_id HAVING COUNT(*) > 1
) x
UNION ALL
SELECT 'items_sin_producto', COUNT(*)
FROM solicitud_laboratorio_items WHERE deleted_at IS NULL AND producto_id IS NULL;

-- 4. Precios: diferencia entre lo guardado y el catálogo, y precios editados en caja.
SELECT 'items_con_precio_distinto_al_catalogo_actual' AS dato, COUNT(*) AS cantidad
FROM solicitud_laboratorio_items i
JOIN productos p ON p.id = i.producto_id
WHERE i.deleted_at IS NULL AND i.precio <> p.precio
UNION ALL
SELECT 'lineas_de_examen_vendidas_con_precio_editado', COUNT(*)
FROM venta_detalles vd
JOIN ventas v ON v.id = vd.venta_id
JOIN productos p ON p.id = vd.producto_id
JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE tp.es_laboratorio = 1 AND vd.deleted_at IS NULL AND v.estado <> 'ANULADO'
  AND vd.precio_original IS NOT NULL AND vd.precio <> vd.precio_original;

-- 5. Posibles cargas repetidas de una misma venta (no hay vínculo; se aproxima):
--    días en que un mismo paciente tiene 2 o más solicitudes. Solo el conteo.
SELECT COUNT(*) AS paciente_dia_con_2_o_mas_solicitudes
FROM (
    SELECT paciente_id, fecha_solicitud
    FROM solicitudes WHERE deleted_at IS NULL
    GROUP BY paciente_id, fecha_solicitud HAVING COUNT(*) > 1
) x;

-- 6. Áreas de laboratorio con nombre de perfil, panel, ionograma, electrolitos o combo.
SELECT tp.id, tp.nombre,
       (SELECT COUNT(*) FROM productos p WHERE p.tipo_producto_id = tp.id AND p.deleted_at IS NULL) AS productos
FROM tipo_productos tp
WHERE tp.deleted_at IS NULL AND tp.es_laboratorio = 1
  AND (tp.nombre LIKE '%PERFIL%' OR tp.nombre LIKE '%PANEL%' OR tp.nombre LIKE '%IONO%'
       OR tp.nombre LIKE '%ELECTRO%' OR tp.nombre LIKE '%COMBO%')
ORDER BY tp.nombre;

-- 7. Productos con PERFIL, IONOGRAMA, PANEL, COMBO o ELECTROLITO en el nombre, con sus datos.
SELECT p.id, p.nombre, p.precio, tp.nombre AS area, tp.es_laboratorio,
       COALESCE(p.grupo_reporte, '') AS grupo_reporte,
       IF(p.deleted_at IS NULL, '', 'ELIMINADO') AS eliminado,
       (SELECT COUNT(*) FROM producto_laboratorio_datos d WHERE d.producto_id = p.id AND d.deleted_at IS NULL) AS cantidad_datos,
       (SELECT GROUP_CONCAT(d.nombre ORDER BY d.orden, d.id SEPARATOR ', ')
          FROM producto_laboratorio_datos d WHERE d.producto_id = p.id AND d.deleted_at IS NULL) AS datos
FROM productos p
LEFT JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE p.nombre LIKE '%PERFIL%' OR p.nombre LIKE '%IONOGRAMA%' OR p.nombre LIKE '%PANEL%'
   OR p.nombre LIKE '%COMBO%' OR p.nombre LIKE '%ELECTROLITO%'
ORDER BY tp.es_laboratorio DESC, p.nombre;

-- 8. Productos de laboratorio con más parámetros (los 20 primeros).
SELECT p.id, p.nombre, tp.nombre AS area, p.precio, COUNT(d.id) AS cantidad_datos
FROM productos p
JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
JOIN producto_laboratorio_datos d ON d.producto_id = p.id AND d.deleted_at IS NULL
WHERE p.deleted_at IS NULL AND tp.es_laboratorio = 1
GROUP BY p.id, p.nombre, tp.nombre, p.precio
ORDER BY cantidad_datos DESC, p.nombre
LIMIT 20;

-- 9. Parámetros de un producto con varios datos que se llaman igual que otro producto
--    de laboratorio suelto (la comparación sigue la collation de la tabla).
SELECT pp.id AS producto_id, pp.nombre AS producto, d.nombre AS parametro,
       p2.id AS suelto_id, p2.nombre AS suelto, p2.precio AS precio_suelto
FROM producto_laboratorio_datos d
JOIN productos pp ON pp.id = d.producto_id AND pp.deleted_at IS NULL
JOIN tipo_productos t1 ON t1.id = pp.tipo_producto_id AND t1.es_laboratorio = 1
JOIN productos p2 ON p2.deleted_at IS NULL AND p2.id <> pp.id
                 AND UPPER(TRIM(p2.nombre)) = UPPER(TRIM(d.nombre))
JOIN tipo_productos t2 ON t2.id = p2.tipo_producto_id AND t2.es_laboratorio = 1
WHERE d.deleted_at IS NULL
  AND (SELECT COUNT(*) FROM producto_laboratorio_datos x WHERE x.producto_id = pp.id AND x.deleted_at IS NULL) > 1
ORDER BY pp.nombre, d.orden;

-- 10. Productos de laboratorio con grupo_reporte (agrupación actual del informe).
SELECT p.id, p.nombre, tp.nombre AS area, p.grupo_reporte, p.orden_reporte
FROM productos p
JOIN tipo_productos tp ON tp.id = p.tipo_producto_id
WHERE p.deleted_at IS NULL AND tp.es_laboratorio = 1 AND TRIM(COALESCE(p.grupo_reporte, '')) <> ''
ORDER BY tp.nombre, p.grupo_reporte, p.orden_reporte;

-- 11. Reactivos vinculados a productos dados de baja (regla "no dar de baja un examen de un perfil").
SELECT s.reactivo_id, r.codigo AS reactivo, s.producto_id, p.nombre AS producto, p.deleted_at
FROM servicio_laboratorio_reactivos s
JOIN reactivos r ON r.id = s.reactivo_id
JOIN productos p ON p.id = s.producto_id
WHERE s.deleted_at IS NULL AND p.deleted_at IS NOT NULL
ORDER BY s.reactivo_id;
