-- SOLO LECTURA. Movimientos de caja cuya fecha_hora cambió al editarse en
-- exactamente +4 h (o un múltiplo de 4 h), con hasta 1 minuto de diferencia
-- porque el formulario anterior además descartaba los segundos.
SELECT
    m.id,
    m.caja,
    m.tipo,
    a.fecha_anterior                         AS fecha_original_en_la_edicion,
    a.fecha_nueva                            AS fecha_despues_de_la_edicion,
    m.fecha_hora                             AS fecha_actual,
    ROUND(a.segundos / 3600, 2)              AS horas_corridas,
    u.name                                   AS usuario,
    a.created_at                             AS fecha_de_la_edicion,
    a.audit_id
FROM (
    SELECT
        au.id                                                                   AS audit_id,
        au.auditable_id,
        au.user_id,
        au.created_at,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(au.old_values, '$.fecha_hora')) AS DATETIME) AS fecha_anterior,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(au.new_values, '$.fecha_hora')) AS DATETIME) AS fecha_nueva,
        TIMESTAMPDIFF(SECOND,
            CAST(JSON_UNQUOTE(JSON_EXTRACT(au.old_values, '$.fecha_hora')) AS DATETIME),
            CAST(JSON_UNQUOTE(JSON_EXTRACT(au.new_values, '$.fecha_hora')) AS DATETIME)) AS segundos
    FROM audits au
    WHERE au.auditable_type LIKE '%CajaMovimiento'
      AND au.event = 'updated'
      AND JSON_EXTRACT(au.old_values, '$.fecha_hora') IS NOT NULL
      AND JSON_EXTRACT(au.new_values, '$.fecha_hora') IS NOT NULL
) a
JOIN caja_movimientos m ON m.id = a.auditable_id
LEFT JOIN users u ON u.id = a.user_id
WHERE ROUND(a.segundos / 14400) >= 1
  AND ABS(a.segundos - ROUND(a.segundos / 14400) * 14400) <= 60
ORDER BY m.id, a.created_at;
