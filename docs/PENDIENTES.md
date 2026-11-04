# Pendientes

Temas abiertos que dependen de una revisión o una decisión, y los ya resueltos.
Regla para todos: **los datos de producción no se corrigen por migración**; se
revisan y, si corresponde, se corrigen desde la pantalla.

## Abiertos

### Movimientos de caja con la hora corrida en producción

Hasta el commit `e4dccfb` (2026-11-04), editar un movimiento de caja desde la
pantalla guardaba la fecha 4 horas más tarde: el formulario tomaba la hora UTC
que manda la API como si fuera hora de Bolivia. Cada edición sumaba otras 4
horas, y los movimientos de la noche podían pasar al día siguiente (y salir del
cierre o del reporte de ese día).

Qué hacer, después del despliegue:

1. Ejecutar en producción la consulta de **solo lectura**
   [`back/scripts/consulta_hora.sql`](../back/scripts/consulta_hora.sql) (en
   phpMyAdmin o en el cliente `mysql`). Lista los movimientos cuya `fecha_hora`
   cambió al editarse exactamente +4 h (o un múltiplo), con id, caja, tipo,
   fecha original, fecha después de la edición, fecha actual, usuario y fecha de
   la edición.
2. La administración revisa **caso por caso** y, si la fecha está mal, la corrige
   desde la pantalla de la caja (Editar → Fecha y hora). Con la corrección ya
   desplegada, esa edición guarda la hora elegida.
3. Ojo: si alguien corrió una fecha a propósito exactamente 4 horas, también
   aparece en la lista. Por eso la revisión es manual.

**Nunca por migración** ni con `UPDATE` directo en la base.

### Categoría para gastos generales

Las categorías de gasto (Ventas y cajas) son los tipos de producto: Laboratorio,
Farmacia, Servicios médicos, Hospitalización e Imagenología. Ninguna sirve para
gastos generales como administración, limpieza o refrigerios.

La administración debe decidir si crea una desde la pantalla de tipos de
producto. **No se crea por migración.** Tenga en cuenta que esa categoría
también aparecerá entre las categorías de productos.

### Comprobantes impresos (`front/src/addons/*Print.js`)

Se revisaron todos tras corregir los de caja y compras. **Ninguno más imprime
fechas de la API recortadas como texto en UTC.** Una sola observación, menor:

- `seguroPrint.js:61`: la línea "Generado: …" usa `new Date().toLocaleString('es-BO')`,
  es decir, la zona horaria del equipo que imprime y no America/La_Paz. Es
  correcta mientras los equipos estén en hora de Bolivia. Sus demás fechas
  (`entrega_informe`, `respuesta_auditoria`, `fecha_facturacion`,
  `fecha_cancelacion`) son columnas `date` que llegan como `AAAA-MM-DD` y se
  imprimen bien.

## Resueltos

- **Hora al editar movimientos de caja** (commit `e4dccfb`, 2026-11-04): el
  formulario convierte la fecha de UTC a hora de Bolivia y, si no se cambia, no
  la reenvía (el backend conserva la original con sus segundos). Los datos que
  ya quedaron corridos se revisan con el punto "Movimientos de caja con la hora
  corrida en producción".
- **Hora de los comprobantes de caja y de compras** (commit `4711d66`,
  2026-11-04): `cajaMovimientoPrint.js` y `compraPrint.js` imprimen la fecha y
  hora de Bolivia.
