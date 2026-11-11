# Pendientes

Temas abiertos que dependen de una revisión o una decisión, y los ya resueltos.
Regla para todos: **los datos de producción no se corrigen por migración**; se
revisan y, si corresponde, se corrigen desde la pantalla.

## Abiertos

| Pendiente | Gravedad | Decide |
|---|---|---|
| [Venta pagada sin vínculo con la solicitud de laboratorio](#venta-pagada-sin-vínculo-con-la-solicitud-de-laboratorio) | Alta | Desarrollo y laboratorio |
| [Perfiles: acceso por URL directa con el interruptor apagado](#perfiles-acceso-por-url-directa-con-el-interruptor-apagado) | Baja | Desarrollo (Fase 2) |
| [Perfiles: Farmacia → productos no los filtra](#perfiles-farmacia--productos-no-los-filtra) | Baja | Desarrollo (Fase 2) |
| [Movimientos de caja con la hora corrida](#movimientos-de-caja-con-la-hora-corrida-en-producción) | Alta | Administración, caso por caso |
| [Categoría para gastos generales](#categoría-para-gastos-generales) | Media | Administración |
| [Comprobantes impresos](#comprobantes-impresos-frontsrcaddonsprintjs) | Baja | Desarrollo |
| [Catálogo de electrolitos y calcio iónico](#catálogo-de-electrolitos-sin-rango-método-ni-muestra-y-unidad-del-calcio-iónico) | Media | Bioquímico |
| [Gasometría 778](#gasometría-778-su-nombre-promete-parámetros-que-no-tiene) | Media | Bioquímico |
| [Fecha por defecto en UTC en "Crear laboratorio"](#fecha-por-defecto-en-utc-en-crear-laboratorio) | Media | Decidido: se mantiene por ahora |
| [Unidad escrita a mano en reactivos](#unidad-escrita-a-mano-en-reactivos-que-no-se-aplica) | Baja | Desarrollo y laboratorio |
| [Reactivos vinculados a productos dados de baja](#reactivos-vinculados-a-productos-dados-de-baja-r-001-r-006-y-r-009) | Baja | Responsable de reactivos, desde la pantalla |
| [Vínculos de reactivos que se recrean al editar](#vínculos-de-reactivos-que-se-borran-y-recrean-al-editar) | Baja | Desarrollo |
| [Tests que fallan por causas previas](#tests-que-fallan-por-causas-previas) | Media | Desarrollo |
| [Datos de prueba en `urme_prueba`](#limpieza-de-los-datos-de-prueba-de-urme_prueba) | Baja | Desarrollo |

### Venta pagada sin vínculo con la solicitud de laboratorio

- **Origen:** diagnóstico de perfiles de exámenes (2026-11-10), en
  [`ANALISIS_PERFILES_FASE0.md`](ANALISIS_PERFILES_FASE0.md), §2 y §9. Queda
  **fuera** del proyecto de perfiles y se trata como un proyecto propio.
- **Gravedad:** alta. En la base, la venta y la solicitud no están relacionadas:
  `solicitudes` no tiene `venta_id` ni existe ninguna otra relación entre ambas.
  "Cargar venta pagada" (`nueva.vue`, `aplicarVentaLaboratorio`) solo rellena el
  formulario con el paciente, el doctor y los exámenes. Por eso:
  1. **La misma venta pagada se puede cargar varias veces** y generar varias
     solicitudes: nada la marca como usada.
  2. **Anular la venta no afecta a la solicitud:** sigue vigente, se imprime y
     cuenta en el kardex de reactivos. Eliminar la solicitud tampoco afecta a la
     venta.
  3. **El precio se toma del catálogo, no del cobrado:** la solicitud guarda
     `productos.precio` vigente, y lo recopia en cada edición, aunque el cajero
     haya cobrado otro precio en la venta.
- **Decide:** desarrollo, con el laboratorio. Las opciones van desde
  `solicitudes.venta_id` (o una tabla de enlace) con aviso si la venta ya se usó,
  hasta qué hacer con la solicitud al anular la venta.

### Perfiles: acceso por URL directa con el interruptor apagado

- **Origen:** Fase 1 de perfiles de exámenes (2026-11-11), en
  [`ANALISIS_PERFILES_FASE0.md`](ANALISIS_PERFILES_FASE0.md), «Estado del proyecto».
- **Gravedad:** baja. Solo pasa si se crearon perfiles con
  `LAB_PERFILES_HABILITADO=true` y después se apagó el interruptor.
  - El catálogo ya no los lista, pero entrando a mano a `/laboratorio/{id}` de un
    perfil se ve la pantalla de un examen común, con «Nuevo dato».
  - El backend igual rechaza agregarle parámetros, fórmulas o validaciones (422).
- **Decide:** desarrollo, en la Fase 2: mostrar un aviso, o volver al catálogo,
  cuando el producto es un perfil y el interruptor está apagado.

### Perfiles: Farmacia → productos no los filtra

- **Origen:** Fase 1 de perfiles de exámenes (2026-11-11).
- **Gravedad:** baja. La pantalla general de productos (`GET /farmacia/datos`)
  lista todos los productos, también los perfiles, con o sin interruptor. Solo se
  nota si existen perfiles. Desde ahí se puede editar un perfil:
  - el backend no deja cambiarlo a un área que no sea de laboratorio;
  - sí deja darlo de baja, lo que no afecta a sus exámenes.
- **Decide:** desarrollo, en la Fase 2: excluir los perfiles de esa pantalla o
  marcarlos con un chip «PERFIL».

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

### Catálogo de electrolitos sin rango, método ni muestra, y unidad del calcio iónico

- **Origen:** revisión del catálogo de laboratorio (2026-10-22).
- **Gravedad:** media. Sodio, potasio, cloro, calcio iónico, calcio total,
  magnesio y fósforo salen en el informe con «-» en valores de referencia,
  método y muestra. El calcio iónico está en mg/dL, igual que el total, y no
  tiene rango propio.
- **Decide:** el bioquímico, desde **Laboratorio → Catálogo de pruebas**.
- **Detalle:** [`PENDIENTES_CATALOGO_LABORATORIO.md`](PENDIENTES_CATALOGO_LABORATORIO.md),
  puntos 1 (electrolitos) y 3 (calcio total y calcio iónico).

### Gasometría 778: su nombre promete parámetros que no tiene

- **Origen:** revisión del catálogo de laboratorio (2026-10-22).
- **Gravedad:** media. El producto 778, «GASOMETRIA ARTERIAL CON NA, K, HTO,
  HEMOGLOBINA», solo tiene pH, pCO2, pO2, bicarbonato, exceso de base,
  saturación de oxígeno y lactato: no tiene sodio, potasio, hematocrito ni
  hemoglobina. Además escribe los rangos con punto decimal (`7.35-7.45`).
- **Decide:** el bioquímico: agregar los parámetros que faltan o corregir el
  nombre, desde el catálogo de pruebas.
- **Detalle:** [`PENDIENTES_CATALOGO_LABORATORIO.md`](PENDIENTES_CATALOGO_LABORATORIO.md),
  punto 6.

### Fecha por defecto en UTC en "Crear laboratorio"

- **Origen:** tarea del código laboratorio (2026-11-02).
- **Gravedad:** media. `nueva.vue` toma la fecha por defecto con
  `toISOString()`, que está en UTC: después de las 20:00 (hora de Bolivia)
  propone la fecha del día siguiente. Se puede corregir a mano en el formulario.
- **Decisión:** se **mantiene así por ahora**, por pedido del equipo. Si se
  corrige, puede reutilizar `nowBoliviaDateTimeInput()` de `addons/dateTime.js`.

### Unidad escrita a mano en reactivos que no se aplica

- **Origen:** tarea de unidades de reactivos (2026-11-03).
- **Gravedad:** baja. En «Nuevo reactivo» o al editar, el campo Unidad permite
  escribir (`use-input`), pero al confirmar con Enter o Tab el campo se queda con
  la opción resaltada, no con el texto escrito: escribir `XYZ` deja `ML`. Solo
  se pueden usar las unidades de la lista, que desde el merge de
  `reactivos-unidades` en `master` (`1484a76`) incluye µL, DETERMINACIÓN y TACO.
- **Decide:** desarrollo, junto con el laboratorio: si debe admitir unidades
  libres, o quitar la escritura y dejar solo la lista.

### Reactivos vinculados a productos dados de baja (R-001, R-006 y R-009)

- **Origen:** R-001, en la tarea de unidades de reactivos (2026-11-03); R-006 y
  R-009, en el diagnóstico de perfiles (2026-11-10).
- **Gravedad:** baja (es un dato). En la copia local, tres reactivos están
  vinculados a productos dados de baja el 2026-10-21. En el listado de
  reactivos, el chip del servicio sale sin nombre.
  - R-001 «REACTIVO DE GLUCOSA» → 286 «GLUCOSA 20% 500 ML».
  - R-006 → 157 «CORTYPIREN GOTAS».
  - R-009 → 74 «BOLSA DE ORINA NIPRO».
- **Confirmar en producción** con la consulta 11 (solo lectura) de
  [`back/scripts/consulta_perfiles_fase0.sql`](../back/scripts/consulta_perfiles_fase0.sql).
- **Decide:** el responsable de reactivos, **desde la pantalla de Reactivos**:
  vincular cada uno al servicio de laboratorio correcto o quitar el vínculo. No
  se corrige por código ni por migración.

### Vínculos de reactivos que se borran y recrean al editar

- **Origen:** tarea de unidades de reactivos (2026-11-03).
- **Gravedad:** baja. Al guardar un reactivo, `ReactivoController@update` borra
  (soft delete) todos sus vínculos con servicios y los crea de nuevo, aunque no
  cambien. No se pierde información, pero cada edición deja filas borradas y
  registros de auditoría que no corresponden a cambios reales.
- **Decide:** desarrollo: sincronizar solo los vínculos que cambian.

### Tests que fallan por causas previas

- **Origen:** estado conocido de la suite (`CLAUDE.md`), revisado el 2026-11-04.
- **Gravedad:** media: hoy la suite no sirve como red de seguridad.
- **Estado en `master`:** `php artisan test` → **8 de 18 fallan** (pasan 10,
  entre ellos los 6 de `ReactivoUnidadTest`, que llegaron con `reactivos-unidades`).
  - `EditarLotesFarmaciaTest` (4) y `LaboratorioDescripcionHtmlTest` (2): corren
    las migraciones sobre SQLite y fallan con «no such table:
    tipo_producto_padres».
  - `ReactivoKardexTest` (2): llaman a `ReactivoKardex::datos()` con 2
    argumentos y la firma ahora pide 3.
  - Además, desde que se neutralizó `2026_09_28_000100_reiniciar_inventario_oficial_farmacia`,
    una instalación desde cero falla en
    `2026_10_06_000100_actualizar_inventario_farmacia_con_precios`, porque espera
    los productos que creaba esa migración.
- **Decide:** desarrollo.

### Limpieza de los datos de prueba de `urme_prueba`

- **Origen:** verificaciones de las tareas del 2026-11-02 al 2026-11-04.
- **Gravedad:** baja. Solo afecta a la base de pruebas, no a `back` ni a producción.
- **Qué quedó:**
  - solicitudes 41 a 50 (incluidas `LAB-PRUEBA-*` y `LAB-ROTULO-*`), pacientes
    11 a 14 y doctores 1 a 3 (todos con «PRUEBA» en el nombre); el paciente 12
    tiene un teléfono ficticio;
  - reactivos 11 a 16 (`P-*`) y la edición de R-001;
  - la venta pagada #1 de prueba, los gastos de Ventas 2 a 6 y los movimientos
    de caja 1 a 14;
  - la categoría borrada «PRUEBA GASTO BORRADA» (#6) y su subcategoría #52;
  - las migraciones de categoría de gasto aplicadas solo en `urme_prueba`;
  - los tokens de prueba ya están revocados.
- **Decide:** desarrollo. Lo más limpio es volver a copiar `urme_prueba` desde un
  respaldo de `back` en lugar de borrar fila por fila.

## Resueltos

- **Hora al editar movimientos de caja** (commit `e4dccfb`, 2026-11-04): el
  formulario convierte la fecha de UTC a hora de Bolivia y, si no se cambia, no
  la reenvía (el backend conserva la original con sus segundos). Los datos que
  ya quedaron corridos se revisan con el punto "Movimientos de caja con la hora
  corrida en producción".
- **Hora de los comprobantes de caja y de compras** (commit `4711d66`,
  2026-11-04): `cajaMovimientoPrint.js` y `compraPrint.js` imprimen la fecha y
  hora de Bolivia.
