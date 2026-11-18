# Análisis del reporte «Cierres de caja» (solo lectura)

Fecha: 2026-11-17. Alcance: Administración → Cierres de caja
(`front/src/pages/index/cierres-caja/index.vue`), `CierreCajaController.php`,
`CierreCajaVentasExport.php`, `reportes/cierre-caja-ventas.blade.php`, el voucher
(`front/src/addons/cierreCajaPrint.js`) y el diálogo de cierre del cajero
(`front/src/components/CajaAcciones.vue`). No se modificó código ni datos.

**Cómo se comprobó.** `back` y `urme_prueba` no tienen ningún cierre de caja
(`back` tampoco tiene ventas), así que la fórmula se comprobó con un escenario
armado en SQLite en memoria, ejecutando el código real del controlador
(`ventasDelDia`, `totalesDelDia`, `recalcular`). No se escribió nada en MySQL.
Ver §2.4.

Rutas: [`back/routes/api.php:244-252`](../back/routes/api.php#L244-L252).

## Resumen

1. **La diferencia compara el efectivo contado contra el total de todo lo
   cobrado, QR incluido.** Un cajero que entrega exactamente el efectivo aparece
   con un **faltante igual a lo cobrado por QR** (y a la parte QR de los pagos
   mixtos). Es el riesgo más grave.
2. **El total del sistema se congela al cerrar.** Si después se anula una venta
   de ese día, el cierre no cambia, pero el detalle (pantalla, PDF, Excel) sí:
   dejan de cuadrar y la venta anulada desaparece del detalle del cierre.
3. **`recalcular()` es inconsistente.** Solo recalcula los cierres que alguna
   vez tuvieron una autorización, y lo hace en cada consulta (también en un
   `GET`) hasta que se validen, aunque la autorización haya vencido.
4. **El «día» es el día de Bolivia**, bien calculado. Pero un turno que cruza
   la medianoche no tiene cómo cerrarse: el cierre toma siempre el día de hoy y
   las ventas de antes de la medianoche quedan sin cierre.

## 1. Pantalla: columnas, filtros, totales, acciones y permisos

### Permisos

| Permiso | Para qué | Dónde se exige |
|---|---|---|
| `Ver Cierres Caja` | Entrar a la pantalla, listar cierres, ver sus ventas, exportar | Ruta: [`router/permissions.js:25`](../front/src/router/permissions.js#L25). API: `index` ([`CierreCajaController.php:34`](../back/app/Http/Controllers/CierreCajaController.php#L34)), `ventas` (:228), Excel (:253), PDF (:266) |
| `Ver Montos Caja` | Ver «Sistema» y «Diferencia» | API: `ocultarMontos()` (:336-343). Front: `canMontos` ([`index.vue:382`](../front/src/pages/index/cierres-caja/index.vue#L382)) |
| `Cerrar Caja` | Cerrar y corregir la caja propia; estado del día | `estado` (:67), `store` (:88), `update` (:129) |
| `Validar Cierres Caja` | Validar un cierre | `validar` (:165); front `canValidar` (index.vue:383) |
| `Autorizar Ventas Caja Cerrada` | Dar tiempo extra para vender | `autorizar` (:194); front `canAutorizar` (index.vue:384) |
| `Ver Usuarios` | Filtro «Usuario» (si falla, el filtro no aparece) | `GET users` ([`UserController.php:89`](../back/app/Http/Controllers/UserController.php#L89)); front `cargarUsuarios` (index.vue:505-512) |

Descripciones en [`Permisos.php:146`](../back/app/Support/Permisos.php#L146) y
[`Permisos.php:151-154`](../back/app/Support/Permisos.php#L151-L154). Quien
tiene `Ver Cierres Caja` ve los cierres de **todos** los usuarios: la API no
filtra por usuario salvo con el filtro `user_id` (:46-48).

En `back` hoy 1 de 4 usuarios tiene cada uno de estos permisos, y ninguno tiene
`Ver Cierres Caja` sin `Ver Montos Caja`. En producción no se comprobó.

### Filtros (index.vue:23-70)

- **Rangos rápidos** (index.vue:395-447): Esta semana (por defecto), Semana
  pasada, Hoy, Este mes, Todo. Se calculan con el reloj del navegador.
- **Desde / Hasta** (fecha del cierre, `whereDate('fecha')`, :40-45). Editarlos
  a mano apaga el rango rápido (index.vue:450-453).
- **Usuario** (:46-48) y **Por página** (15/25/50/100).

### Columnas (index.vue:74-86)

| Columna | Origen |
|---|---|
| Opciones | Menú de acciones |
| Fecha | `fecha` del cierre |
| Usuario | `user.name` |
| Ventas del día | `cantidad_ventas`: cantidad de **ingresos**, sin gastos (:510) |
| Sistema (Bs) | `monto_sistema` (solo con `Ver Montos Caja`) |
| Declarado (Bs) | `monto`: efectivo que digitó el cajero |
| Diferencia | `diferencia`, como sobrante (+) o faltante (−) (index.vue:467-472). Solo con `Ver Montos Caja` |
| Cerrado el | `fecha_hora` |
| Corrección | `modificado_en` |
| Validación | VALIDADO (quién y cuándo) o SIN VALIDAR, más «Vendiendo / Autorizado hasta» |
| Comentario | `comentario` |

### Totales del encabezado (index.vue:33-48)

Sistema, Declarado y Diferencia se calculan en el navegador sumando
**solo los cierres de la página visible** (index.vue:456-458), no todos los
del filtro. Ver riesgo 7.

### Acciones (index.vue:97-133)

- **Validar cierre**: con `Validar Cierres Caja` y si no está validado.
- **Autorizar más ventas**: con `Autorizar Ventas Caja Cerrada`, sin validar y
  si es de hoy según el navegador (`esHoy`, index.vue:543). Opciones de 15 min a
  «Resto del día» (720 min), tope a medianoche (:207).
- **Ver todas las ventas**: abre un diálogo con los movimientos del cierre
  (index.vue:213-368). Tiene un filtro por fecha y hora («24 h antes del
  cierre», «Día del cierre») y Voucher, Excel y PDF.
- **Imprimir voucher**, **Exportar Excel**, **Exportar PDF**.

### Lado del cajero (`CajaAcciones.vue`)

El cajero cierra con «Efectivo que entrega (Bs)»
([`CajaAcciones.vue:155`](../front/src/components/CajaAcciones.vue#L155)). El
conteo por cortes de billetes es solo una ayuda (:309-330 del controlador). El
cierre es «a ciegas»: sin `Ver Montos Caja` no ve el total del sistema (:61-83).

## 2. Fórmula de `monto_sistema` y de la diferencia

### 2.1 Qué movimientos entran: `ventasDelDia()` (:482-495)

Para el usuario `U` y la fecha `F`:

```
( user_id = U  AND estado = 'ACTIVO'    AND DATE(fecha_hora)       = F )
OR ( cobrado_por_id = U AND estado = 'PENDIENTE' AND DATE(fecha_hora_cobro) = F )
```

| Caso | ¿Entra? | Por qué |
|---|---|---|
| Venta normal (EFECTIVO, QR, MIXTO) | Sí, en la caja de quien la registró, el día que se registró | `store` crea `ACTIVO` con `fecha_hora = now()` ([`VentaController.php:294`](../back/app/Http/Controllers/VentaController.php#L294), :307) |
| Venta pendiente **sin cobrar** | No | `estado = PENDIENTE` y sin `fecha_hora_cobro` |
| Venta pendiente **cobrada** | Sí, en la caja de **quien cobró** y el **día del cobro** | Al cobrar sigue en `PENDIENTE` pero guarda `cobrado_por_id` y `fecha_hora_cobro` (VentaController.php:500-504) |
| Venta anulada | No | `estado = ANULADO` (VentaController.php:523) |
| Cobro de internación | Sí, como ingreso | [`CobroInternacion.php:47-58`](../back/app/Services/CobroInternacion.php#L47-L58) crea la venta `ACTIVO` sin `tipo_movimiento`; la columna toma `INGRESO` por defecto ([migración `2026_09_10_000200`:16](../back/database/migrations/2026_09_10_000200_add_tipo_movimiento_to_ventas_table.php#L16)) |
| Gasto de caja (Ventas → «Nuevo gasto») | Sí, se resta | `EGRESO`, `ACTIVO` (VentaController.php:435-450) |
| Gastos de Caja administrativa / Caja general (`caja_movimientos`) | No | Son otras tablas y otras cajas |

### 2.2 Fórmula: `totalesDelDia()` (:501-512)

```
monto_sistema   = Σ total (tipo_movimiento = INGRESO) − Σ total (tipo_movimiento = EGRESO)
cantidad_ventas = cantidad de INGRESO
diferencia      = monto (efectivo declarado) − monto_sistema      (store :113, update :151, recalcular :464)
```

- Se suma `total`, **no** `monto_efectivo` ni `monto_qr`. **No se separa el
  efectivo del QR.**
- El gasto se resta siempre, cualquiera sea su `tipo_pago`. El formulario no
  envía tipo de pago (queda EFECTIVO) y el backend guarda
  `monto_efectivo = monto` (VentaController.php:441, :449).

### 2.3 ¿La diferencia compara el efectivo contado solo contra el efectivo esperado?

**No.** El cajero declara el efectivo que entrega (CajaAcciones.vue:155), pero
se compara contra el total de todo lo cobrado: efectivo, QR, la parte QR de los
mixtos y los cobros de internación con cualquier tipo de pago.

- Toda venta por QR aparece como **faltante** del mismo importe.
- La columna «Diferencia» solo es confiable en un día sin cobros por QR.

Los datos para separarlo ya existen: cada venta guarda
`monto_efectivo + monto_qr = total` (VentaController.php:530-579; en `urme_prueba`
las 3 ventas cumplen la igualdad). La única excepción son los cobros de
internación (riesgo 6).

### 2.4 Ejemplo comprobado

Script: `scratchpad/cierres/ejemplo.php`. Usa el código real del controlador
sobre SQLite en memoria. Escenario del usuario 1, día 16/11:

| Mov. | Detalle | Total | Efectivo | QR |
|---|---|---|---|---|
| V1 | Venta EFECTIVO 09:00 | 100 | 100 | 0 |
| V2 | Venta QR 10:00 | 50 | 0 | 50 |
| V3 | Venta MIXTO 10:30 | 80 | 50 | 30 |
| V4 | Pendiente de otro usuario (15/11), cobrada por 1 el 16 a las 11:00 | 40 | 40 | 0 |
| V5 | Cobro de internación EFECTIVO 12:00 | 200 | 0 (*) | 0 (*) |
| G1 | Gasto 13:00 | −20 | −20 | |
| V6 | Venta EFECTIVO **21:30** | 30 | 30 | 0 |
| V7 | Pendiente sin cobrar | 60 | — | — |
| V8 | Anulada | 25 | — | — |

(*) Así las crea `CobroInternacion` (riesgo 6). El efectivo real en el cajón es
400 Bs.

Resultado del código:

- Entran V1–V6 y G1; V7 y V8 no. `cantidad_ventas = 6`.
- **`monto_sistema = 480`** (= 100 + 50 + 80 + 40 + 200 + 30 − 20).
- Si el cajero declara exactamente sus **400** de efectivo, **diferencia = −80
  (faltante)**: justo lo cobrado por QR (50 + 30).
- La venta de las 21:30 entra en el día 16: el día es el de Bolivia (§6).

## 3. Cuándo se calcula y se recalcula

| Momento | Qué pasa | Código |
|---|---|---|
| **Cerrar** (`store`) | Calcula `monto_sistema`, `cantidad_ventas` y `diferencia` y los guarda. Si ya cerró hoy, devuelve el mismo cierre sin recalcular | :86-124 (:96-101) |
| **Corregir** (`update`, una sola vez, el mismo usuario, sin validar) | Cambia el efectivo declarado. La diferencia usa el `monto_sistema` guardado; solo lo recalcula si hubo autorización | :127-160 |
| **Validar** | Llama a `recalcular()` y luego marca validado. Rechaza si hay una autorización vigente | :163-185 |
| **Autorizar** | Pone `autorizado_hasta` (tope 23:59:59 de hoy) y `modificado_en = null`, lo que devuelve la corrección | :192-219, :207, :212 |
| **Listar / ver ventas / exportar** | Llaman a `recalcular()`, que **guarda** si cambió algo | :53, :71, :230, :255, :269 |
| `recalcular()` | **Solo** si `autorizado_hasta` no es nulo y el cierre no está validado: recalcula `monto_sistema`, `cantidad_ventas` y `diferencia` y guarda | :454-471 |

### Venta o gasto anulado después del cierre

- `destroy` anula cualquier venta o gasto sin mirar si la caja de ese día está
  cerrada o validada (VentaController.php:514-526). Basta `Eliminar Ventas`,
  incluso sobre la venta de otro usuario.
- **Cierre sin autorización:** `monto_sistema` y `diferencia` **no cambian**.
  Pero el detalle (`ventasDelDia` filtra por estado) ya no incluye la venta. La
  pantalla, el PDF, el Excel y el voucher la pierden, y su suma deja de
  coincidir con «Sistema».
- **Cierre que tuvo alguna autorización y no está validado:** el próximo `GET`
  recalcula y **sobrescribe** `monto_sistema` y `diferencia`, aunque la
  autorización haya vencido hace días.

Ejemplo comprobado (el mismo de §2.4, cerrado a las 21:40 con 400 declarados):

- Se anula V1 (100) a las 22:10. `monto_sistema` sigue en **480** y el detalle
  suma **380**.
- Si ese cierre tuvo una autorización, `recalcular()` lo pasa a **380**, con
  diferencia **+20**.

### Edición de ventas

No existe edición de montos: no hay `PUT /ventas/{id}`, y el permiso
`Editar Ventas` (Permisos.php:47) no lo usa ningún controlador. Una venta solo
cambia al cobrar una pendiente (`completar`) o al anularla.
`ProductoLoteController` edita datos del lote, no importes.

### Bloqueo de la caja cerrada

`cajaBloqueada()` (:443-448) impide vender, registrar gastos, cobrar pendientes
y cobrar internaciones con la caja de **hoy** cerrada:

- VentaController.php:266, :419 y :474;
- [`InternacionController.php:168`](../back/app/Http/Controllers/InternacionController.php#L168);
- [`PacienteController.php:102`](../back/app/Http/Controllers/PacienteController.php#L102).

Solo mira el día de hoy.

## 4. Categoría de gasto y cambios recientes de ventas

| Campo | ¿Afecta el total? | ¿Aparece en el reporte? |
|---|---|---|
| Categoría y subcategoría de gasto (`tipo_producto_padre_id`, `tipo_producto_id`; `CategoriaGasto::validar`, VentaController.php:429 y :436) | No: el gasto resta su `total` cualquiera sea la categoría | No. Pantalla, PDF y Excel muestran la descripción (ítem) del gasto, no su categoría. El voucher: «GASTO: descripción» |
| `tipo_paciente` | No | No (ni filtro ni columna) |
| `servicio` | No | No |
| `con_factura` / `numero_factura` | No | No (tiene su propio Excel en Ventas, `export-facturadas`) |

Los cobros de internación no guardan `tipo_paciente` ni `servicio`. Para el
cierre da igual.

## 5. PDF, Excel y voucher del detalle

| | Contenido | Totales | ¿Coincide con la pantalla? |
|---|---|---|---|
| **PDF** (`cierre-caja-ventas.blade.php`) | Cajero, fecha, **Sistema**, Declarado, **Diferencia** (:47-51); con rango, el resumen del rango (:56-67); tabla de movimientos (N°, fecha y hora, cliente o paciente con ítems y comentario, tipo, estado, pago, ítems, total con gastos en negativo) (:72-106) | Pie «Total del cierre (ventas menos gastos)» = suma **en vivo** de los movimientos (:109-110). «Ventas: N» del encabezado cuenta también los gastos (:39) | Sistema y Diferencia son los guardados, igual que la pantalla. El pie es en vivo: **no coincide** con «Sistema» si se anuló algo después del cierre sin recalcular |
| **Excel** (`CierreCajaVentasExport`) | Una fila por movimiento: N°, fecha y hora, cliente o paciente, **CI**, tipo, estado, pago, ítems, detalle, comentario, total con signo (:28-44) | **Sin fila de totales** ni Sistema, Declarado o Diferencia | La suma de la columna Total es el neto en vivo, con la misma salvedad del PDF |
| **Voucher** (`cierreCajaPrint.js`) | Ventas y gastos del día, Sistema y Diferencia (solo con `verMontos`), EFECTIVO DECLARADO, «Por forma de pago», lista de movimientos (:147-230) | «Por forma de pago» agrupa por `tipo_pago`: MIXTO no se parte en efectivo y QR, y los gastos restan dentro de EFECTIVO (:133-145) | Sistema guardado más formas en vivo: tampoco cuadran si hubo anulaciones |

Sin anulaciones posteriores, los tres coinciden con la pantalla. Con el filtro
de fecha y hora, PDF, voucher y pantalla usan el mismo `resumen()` del rango
(:419-431).

## 6. Zona horaria

- **El día del cierre es el de Bolivia.** `config/app.php:70` usa
  `America/La_Paz`. `hoy()` es `now()->toDateString()` (:473-476). Las ventas
  guardan `fecha_hora = now()` en hora local (VentaController.php:307), y
  `whereDate` compara `DATE(fecha_hora)` contra esa fecha.
  - En el ejemplo, la venta de las **21:30** quedó en su día.
  - En las dos bases, PHP y MySQL dan la misma hora local.
  - No hay corrimiento a UTC en el cálculo.
- **La API devuelve fechas en UTC** (ISO). El front las muestra en hora de
  Bolivia (`addons/dateTime.js:1`).
- **El filtro por fecha y hora** se interpreta en hora de Bolivia
  (`rangoSolicitado`, :355-378). «Hasta» incluye el minuto completo (:395).
- **Excepción, el navegador:** los rangos rápidos y `esHoy` usan la fecha del
  equipo (index.vue:404-447, :543). En una PC con otra zona u hora mal puesta,
  el menú puede mostrar u ocultar «Autorizar» por error. El backend igual
  rechaza autorizar un cierre que no es de hoy (:202).
- **Antecedente:** el problema conocido de «hora corrida +4 h» fue en la
  **edición de movimientos de caja** (`caja_movimientos`), no en ventas (ver
  [`PENDIENTES.md`](PENDIENTES.md), «Movimientos de caja con la hora corrida en
  producción»). No afecta a este cierre.
- **Turno que cruza la medianoche:** quien cierra a las 00:30 crea el cierre del
  **día nuevo** (`hoy()`), con 0 ventas en el ejemplo. Las ventas de antes de
  la medianoche quedan **sin cierre**, y no hay forma de cerrar un día anterior.
  El filtro «24 h antes del cierre» solo sirve para consultar: no cambia el
  monto guardado.

## 7. Riesgos e inconsistencias, por gravedad

### Alta

1. **Diferencia contra el total, no contra el efectivo.**
   - El cajero declara efectivo (CajaAcciones.vue:155), pero `monto_sistema`
     incluye el QR y la parte QR de los mixtos (:501-512).
   - Cada cobro por QR aparece como faltante (−80 en el ejemplo con la caja
     exacta).
   - Lo mismo en el voucher y el PDF.
   - Los datos para separarlo ya están en `monto_efectivo` y `monto_qr`.
2. **Cierre congelado frente a anulaciones posteriores.**
   - `destroy` no controla cierres (VentaController.php:514-526).
   - El cierre guardado no cambia, pero el detalle sí: «Sistema» (480) contra
     el pie del PDF (380) en el ejemplo.
   - La venta anulada desaparece del detalle del cierre, así que no queda
     rastro en el reporte de que entró a la caja ese día.
   - Pasa también con cierres **validados**.
3. **`recalcular()` con dos comportamientos.**
   - Recalcula solo los cierres que alguna vez tuvieron `autorizado_hasta`, y
     siempre hasta que se validen, aunque la autorización ya haya vencido
     (:454-471).
   - Escribe en la base desde lecturas (`GET` del listado, de las ventas y de
     las exportaciones: :53, :230, :255, :269).
   - Dos cierres iguales responden distinto a la misma anulación según si
     alguna vez se autorizó.

### Media

4. **Turnos que cruzan la medianoche y días sin cierre.**
   - El cierre siempre es del día de hoy (:93, :473-476).
   - Las ventas de antes de la medianoche quedan sin cierre.
   - `cajaBloqueada` solo mira hoy: quien no cerró ayer vende normalmente hoy, y
     nada avisa que el día anterior quedó abierto.
   - En `urme_prueba` hay 3 combinaciones usuario-día con ventas activas y sin
     cierre (son datos de prueba).
5. **Montos visibles sin `Ver Montos Caja`.**
   - El **PDF** muestra Sistema y Diferencia sin mirar el permiso
     (blade :49-51).
   - El diálogo de ventas muestra el total de cada movimiento y el banner del
     rango (ingresos, gastos, neto) (index.vue:264-280, :351-353).
   - El **voucher** muestra «Por forma de pago», cuya suma es el sistema
     (cierreCajaPrint.js:205-209).
   - En `back` nadie tiene `Ver Cierres Caja` sin `Ver Montos Caja`, pero si en
     producción alguien lo tiene, el cierre deja de ser «a ciegas» para esa
     persona.
6. **Cobros de internación sin reparto efectivo/QR.**
   - `CobroInternacion` no llena `monto_efectivo` ni `monto_qr` (quedan en 0) ni
     `total_original`, y su `tipo_pago` es texto libre (CobroInternacion.php:47-58).
   - En el cierre cuentan por `total`, así que hoy el total está bien.
   - Pero el resumen de efectivo/QR de Ventas no los suma
     (VentaController.php:105-107).
   - Además, si se corrige el riesgo 1 usando `monto_efectivo`, **estos cobros
     quedarían fuera**: hay que corregirlos antes o a la vez.

### Baja

7. **Totales del encabezado solo de la página visible** (index.vue:456-458).
   Con más cierres que «Por página», el total de la semana sale incompleto.
8. **Conteos que no dicen lo mismo.** «Ventas del día» y `cantidad_ventas`
   cuentan solo ingresos (:510); el «Ventas: N» del PDF cuenta también los
   gastos (blade :39).
9. **«Por forma de pago» del voucher** agrupa MIXTO entero y resta los gastos
   dentro de EFECTIVO (cierreCajaPrint.js:133-145). No sirve como arqueo de
   efectivo.
10. **Gastos de Caja administrativa y Caja general** (`caja_movimientos`) no
    entran al cierre. Probablemente sea a propósito porque son otras cajas,
    pero conviene confirmarlo si salen del mismo cajón.
11. **Fechas del navegador** en los rangos rápidos y en `esHoy` (§6).
12. **Permiso `Editar Ventas` sin uso** (Permisos.php:47).
13. **Datos personales:** el Excel incluye el CI del paciente
    (CierreCajaVentasExport.php:32) para quien tenga `Ver Cierres Caja`.

## Preguntas para decidir antes de cambiar algo

- ¿La diferencia del cierre debe ser **solo de efectivo**? (efectivo esperado =
  `Σ monto_efectivo` de los ingresos − gastos en efectivo, y el QR informado
  aparte para conciliar con el banco).
- ¿Se debe impedir anular una venta de un día con la caja cerrada o validada, o
  registrar la anulación como movimiento del día en que se hace?
- ¿`recalcular()` debe dejar de correr después de que vence la autorización (o
  al corregir), y nunca en un `GET`?
- ¿Cómo se cierra un turno nocturno: con la fecha del inicio del turno, o con un
  cierre por rango de horas?

> **Ya implementado después de este análisis** (commit `352d748`, 2026-11-17):
> el bloque «Resumen de caja» al pie del detalle del cierre (pantalla, PDF y
> Excel). Su alcance es «lo registrado por el usuario en su día». El diseño de
> abajo lo tiene en cuenta (§8.11).

## 8. Diseño de mejoras (Paso A: solo diseño, sin implementar)

Decisiones tomadas: la diferencia es solo de efectivo; las anulaciones con la
caja cerrada se bloquean; `recalcular()` nunca corre en un GET; se puede cerrar
un día anterior; los montos se respetan en todas las salidas; los cobros de
internación guardan el reparto; y hay un reporte completo para los cierres
validados. Reglas: solo migraciones de esquema **aditivas**, y **ningún dato
existente se modifica**, incluidos los cierres ya guardados.

### 8.1 Definiciones (una sola fuente para todo)

**Movimientos de la caja** (`M`): exactamente los de hoy, `ventasDelDia(U, D)`
(:482-495).

- Ventas `ACTIVO` registradas por `U` en `D`.
- Pendientes que **cobró** `U` en `D`, las haya registrado quien sea y cuando
  sea. El dinero está en el cajón de quien cobra.
- Gastos `EGRESO`/`ACTIVO` de `U` en `D`.

**Reparto de cada movimiento en medios de pago** (nuevo helper
`MedioPago::reparto($venta)`, también como fragmento SQL, al estilo de
`StockLote`):

| Caso | Efectivo | QR | Otros (transferencia, tarjeta) |
|---|---|---|---|
| Venta con reparto guardado (`monto_efectivo + monto_qr = total`) | `monto_efectivo` | `monto_qr` | 0 |
| Cobro **antiguo** de internación sin reparto (`monto_efectivo = monto_qr = 0`, `total > 0`) | `total` si `tipo_pago` = EFECTIVO (o vacío) | `total` si `tipo_pago` = QR | `total` si `tipo_pago` = TRANSFERENCIA o TARJETA |
| Cobro de internación nuevo | lo guarda `CobroInternacion` (§8.4) | ídem | se identifica por `tipo_pago` (no tiene columna propia) |
| Gasto | `total` (hoy siempre se paga en efectivo, §8.6) | 0 | 0 |

La regla para los antiguos **solo se calcula al leer**: no modifica ningún dato
guardado.

**Fórmula nueva:**

```
efectivo_esperado   = Σ efectivo(ingresos de M) − Σ gastos en efectivo de M
qr_total            = Σ qr(ingresos de M)               (se informa, no entra en la diferencia)
otros_total         = Σ otros(ingresos de M)            (se informa, no entra en la diferencia)
diferencia_efectivo = efectivo declarado − efectivo_esperado
control:  Σ total(ingresos de M) = Σ efectivo + qr_total + otros_total
```

### 8.2 Cambios de esquema (solo aditivos, todas las columnas nullable)

**`cierres_caja`**: las cifras que hoy no se guardan.

| Columna | Tipo | Contenido |
|---|---|---|
| `formula` | string(10), null | `'EFECTIVO'` para los cierres hechos con la fórmula nueva; `null` = cierre antiguo |
| `ingresos_total` | decimal(14,2), null | Σ total de los ingresos de `M` |
| `efectivo_ingresos` | decimal(14,2), null | Σ efectivo de los ingresos |
| `qr_total` / `qr_cantidad` | decimal / int, null | QR y su cantidad de transacciones |
| `otros_total` / `otros_cantidad` | decimal / int, null | transferencia y tarjeta (solo internaciones) |
| `gastos_efectivo` / `gastos_cantidad` | decimal / int, null | gastos de `M` |
| `efectivo_esperado` | decimal(14,2), null | fórmula de §8.1 |
| `diferencia_efectivo` | decimal(14,2), null | declarado − esperado |

`monto_sistema` y `diferencia` **se siguen guardando como hasta hoy** (total −
gastos), para no cambiar su significado histórico. La pantalla deja de
mostrarlos como diferencia principal.

**`ventas`**: para saber cuándo y quién anuló.

| Columna | Contenido |
|---|---|
| `anulado_en` (datetime, null) | Momento de la anulación |
| `anulado_por_id` (FK users, null) | Quién anuló |

**Permiso nuevo** «Anular Ventas Caja Cerrada», en el módulo Caja: se agrega en
`Permisos::MODULOS` y con una migración que lo crea y se lo da a `admin`, como
`2026_09_26_000500`. **Ojo:** esta migración **agrega** filas (`permissions` y
`model_has_permissions`) aunque no modifica ninguna existente. Es la vía de
`CLAUDE.md` para los permisos; si no la apruebas, el permiso se crea desde la
pantalla de usuarios.

**Cierres antiguos** (`formula = null`): no se tocan. El reporte y la pantalla
calculan **al vuelo**, sin guardar, el efectivo esperado y la diferencia de
efectivo con la regla de §8.1. Muestran:

- «Diferencia de efectivo (recalculada)», con la cifra nueva;
- «Diferencia registrada al cerrar (fórmula anterior, incluía QR)», con la cifra
  guardada;
- la nota: «*Este cierre se hizo con la fórmula anterior, que comparaba el
  efectivo con el total incluido el QR. La diferencia de efectivo se recalculó
  con los movimientos actuales; si alguna venta se anuló después del cierre,
  puede no coincidir con lo que había en caja ese día.*»

Para un cierre antiguo, las anulaciones posteriores se detectan **solo para
informar** con `audits` (evento `updated` con `estado` → ANULADO) y, si no hay
auditoría, con `updated_at > cierre.fecha_hora`. No se reconstruye el pasado.

### 8.3 `CierreCajaController`

| Método | Cambio |
|---|---|
| `calcular(U, D)` (nuevo, reemplaza a `totalesDelDia`) | Devuelve todas las cifras de §8.1 más `monto_sistema` antiguo. Es la única fuente: la usan el cierre, el reporte, la pantalla y el bloque «Resumen de caja» |
| `store` | Guarda las cifras nuevas con `formula = 'EFECTIVO'`. El cierre deja de ser solo del día de hoy (§8.7) |
| `update` (corrección) | **Recalcula y guarda** (operación de escritura): `efectivo_esperado`, `diferencia_efectivo` y las demás |
| `validar` | Recalcula y guarda **una última vez**; después, fijo para siempre |
| `autorizar` | No recalcula. Cada venta, gasto o cobro hecho **durante la autorización** llama a `recalcular()` al guardarse (en `VentaController::store`, `gasto`, `completar`, cobro de internación). Ahí está el «tras una operación autorizada» |
| Anulación con permiso sobre caja cerrada | Llama a `recalcular()` del cierre afectado (§8.5) |
| `index`, `estado`, `ventas`, exportaciones | **Ya no llaman a `recalcular()`**: leen lo guardado (y calculan al vuelo, sin guardar, para los antiguos) |
| `recalcular()` | Solo con el cierre **sin validar**; guarda las cifras nuevas y las de la fórmula antigua |
| `reporte` / `reportePdf` / `reporteExcel` (nuevos) | `GET cierres-caja/{id}/reporte[/pdf/excel]`. 403 sin `Ver Cierres Caja` **o** sin `Ver Montos Caja`; 422 si el cierre no está validado |
| `ocultarMontos()` | Oculta también las columnas nuevas. El PDF del detalle y el voucher reciben `verMontos` (§8.8) |

### 8.4 `CobroInternacion` y sus controladores

- `registrar()` reparte el cobro como `VentaController::montosPago`:
  - EFECTIVO: `monto_efectivo = total`, `monto_qr = 0`;
  - QR: `monto_qr = total`;
  - TRANSFERENCIA y TARJETA: los dos en 0. Se identifican por `tipo_pago` y van
    a «Otros medios».
- También guarda `total_original = total` y `tipo_paciente = 'INTERNO'` (hoy
  quedan nulos). **Decide:** ¿INTERNO es correcto para internaciones?
- `InternacionController::pagarTotal` y `PacienteController::cobrarTodo` validan
  `tipo_pago` contra la lista (EFECTIVO, QR, TRANSFERENCIA, TARJETA) en vez de
  texto libre.
- **MIXTO** no existe hoy en internaciones. Si se quiere, requiere pedir el
  monto QR en la pantalla del paciente: queda fuera de esta fase.

### 8.5 `VentaController::destroy` (anulación)

1. Se busca el cierre que **contiene** el movimiento:
   - venta `ACTIVO` o gasto: `(user_id, DATE(fecha_hora))`;
   - pendiente cobrada: `(cobrado_por_id, DATE(fecha_hora_cobro))`;
   - pendiente sin cobrar: no pertenece a ninguna caja, se anula como hoy.
2. Sin cierre: se anula como hoy.
3. Cierre **validado**: 422 «*El cierre de caja del dd/mm ya fue validado: la
   venta no se puede anular*».
4. Cierre sin validar: sin «Anular Ventas Caja Cerrada», 422 «*La caja del dd/mm
   ya fue cerrada…*». Con el permiso, se anula y se llama a `recalcular()` de
   ese cierre.
5. Siempre se guarda `anulado_en = now()` y `anulado_por_id`. El reporte marca
   las anuladas **después del cierre** (`anulado_en > cierre.fecha_hora`).

### 8.6 Gastos («Nuevo gasto»)

- **Confirmado:** se pagan en efectivo.
  - El formulario ([`CajaAcciones.vue:347`](../front/src/components/CajaAcciones.vue#L347))
    no envía medio de pago.
  - El backend guarda `tipo_pago = 'EFECTIVO'` y `monto_efectivo = monto`
    (VentaController.php:441, :449).
  - Los 5 gastos de `urme_prueba` son EFECTIVO.
- Entran al efectivo esperado restando su `total`.
- Si algún día se pagan gastos por QR, se agrega el selector y se resta solo su
  `monto_efectivo`. La fórmula ya lo soporta.

### 8.7 Turno de noche: cerrar un día anterior

**Propuesta (recomendada):** cierre de una jornada anterior pendiente.

- **Qué se puede cerrar:** el día más antiguo, dentro de los **últimos 2 días**,
  en que el usuario tiene movimientos y **no tiene cierre**. Se elige solo, no
  se escribe a mano. `GET cierres-caja/estado` devuelve también
  `jornada_pendiente: {fecha, cantidad}`.
- **Quién:**
  - el **propio cajero**, hasta las **12:00 del día siguiente** (cubre el turno
    de noche que termina por la mañana);
  - después, solo quien tiene «Autorizar Ventas Caja Cerrada», que cierra **en
    nombre** del cajero, con el efectivo que este entregó y un comentario
    obligatorio.
- **Cómo:** `POST cierres-caja` con `fecha` opcional. Sin `fecha`, cierra hoy
  como siempre. Con `fecha`, debe ser la jornada pendiente; si no, 422.
- **Al cajero:** si tiene una jornada pendiente, «Cerrar caja» le ofrece
  primero «*Tiene la caja del dd/mm sin cerrar*». No se bloquea la venta de hoy,
  para no frenar la atención; solo se avisa.
- El índice único (usuario, fecha) sigue valiendo.
- **Alternativa no recomendada ahora:** cierres por rango (desde el cierre
  anterior hasta ahora). Resuelve el turno de noche de raíz, pero cambia el
  modelo de un cierre por día.

### 8.8 Montos según «Ver Montos Caja»

| Salida | Hoy | Cambio |
|---|---|---|
| PDF del detalle | Sistema y Diferencia siempre | Sin el permiso no se muestran |
| Voucher | «Por forma de pago» siempre | Sin el permiso se oculta |
| Diálogo «Ver ventas» | Total de cada venta y banner del rango | Sin el permiso, la columna Total y el banner muestran «—» (la API no los envía) |
| Resumen de caja (`352d748`) | Ya respeta el permiso | — |
| Reporte completo | — | Exige el permiso (403) |

### 8.9 Reporte de cierre de caja

**Dónde:** en **Opciones** de cada cierre, un ítem nuevo «Reporte de cierre de
caja», encima de «Ver todas las ventas».

- Solo aparece con el cierre **VALIDADO** y con los dos permisos; si no, no se
  muestra.
- Abre un diálogo con las secciones y los botones PDF y Excel (una hoja por
  sección).
- Cifras: las **guardadas** del cierre validado. Si es un cierre antiguo, las
  calculadas al vuelo con la nota de §8.2.

**Consultas por sección** (`M` y el reparto de §8.1; `U` y `D` del cierre):

| # | Sección | Consulta |
|---|---|---|
| 1 | Encabezado | `cierres_caja` + `user`, `validadoPor`, `autorizadoPor`: cajero, fecha, `fecha_hora`, corrección, validado por y cuándo |
| 2 | Arqueo de efectivo | Esperado (`efectivo_esperado`), declarado (`monto`) con desglose (`detalle_efectivo`: corte × cantidad), diferencia (`diferencia_efectivo`) |
| 3 | Otros medios | QR: `qr_total` y `qr_cantidad`. Otros: Σ total de los ingresos de `M` con `tipo_pago` TRANSFERENCIA o TARJETA, agrupado por medio |
| 4a | Ventas por forma de pago | Ingresos de `M` agrupados en EFECTIVO, QR, MIXTO-efectivo, MIXTO-QR y OTROS (con el reparto) |
| 4b | Por categoría | `venta_detalles` de los ingresos de `M` → `productos` → `tipo_productos` → `tipo_producto_padres` (con eliminados); Σ `venta_detalles.total` por padre. Ítems sin producto (internación, sueltos): «SIN CATEGORÍA» |
| 4c | Por tipo de paciente | Ingresos de `M` por `ventas.tipo_paciente` (nulo: «SIN DATO»), cantidad y Σ total |
| 4d | Cobros de internación | Ingresos de `M` con `internaciones.venta_id = ventas.id`: cantidad, total y reparto |
| 4e | Pendientes de otros días cobradas hoy | `estado = PENDIENTE`, `cobrado_por_id = U`, `DATE(fecha_hora_cobro) = D`, y además `DATE(fecha_hora) ≠ D` o `user_id ≠ U` |
| 4f | Totales | Cantidad de ventas y Σ total de los ingresos de `M` |
| 5 | Gastos | EGRESO/ACTIVO de `M` + `categoriaGasto` y `subcategoriaGasto`, con su ítem (descripción): listado y total por categoría |
| 6a | Anuladas del día | `user_id = U`, `DATE(fecha_hora) = D`, `ANULADO`, separadas en antes y después del cierre por `anulado_en` (antiguos: auditoría o `updated_at`) |
| 6b | Precio modificado | `venta_detalles` de los ingresos de `M` con `precio ≠ precio_original`: ítem, lista, cobrado y diferencia |
| 6c | Pendientes sin cobrar | `user_id = U`, `DATE(fecha_hora) = D`, `PENDIENTE`, `fecha_hora_cobro` nula |
| 7 | Detalle | `M` ordenado por hora: N.º, hora, paciente o cliente, ítems (producto × cantidad × precio = subtotal), forma de pago, efectivo, QR y estado |
| 8 | Firmas | Cajero y supervisor (quien validó) |

**Cuadres que el reporte verifica** (y muestra ⚠ si fallan):
- 4a = 4b = 4c = 4f;
- efectivo de 4a − gastos de 5 = esperado de 2;
- QR de 4a = 3;
- Σ del detalle 7 = 4f − 5.

### 8.10 Pantalla del cajero (Ventas → «Cerrar caja», `CajaAcciones.vue`)

- **Al cerrar, igual que hoy:** declara a ciegas el «Efectivo que entrega» (con
  el conteo de cortes opcional). Nuevo: si tiene una jornada anterior
  pendiente, se le ofrece cerrarla primero (§8.7).
- **Ya cerrado, con «Ver Montos Caja»:**
  - «Efectivo esperado» en lugar de «Total sistema»;
  - «Diferencia de efectivo»;
  - una línea informativa «Cobrado por QR: Bs X (n)», más «Otros medios» si los
    hay.
- **Ya cerrado, sin el permiso:** solo lo declarado, como hoy.
- **Cierres de caja (administración):**
  - las columnas «Sistema» y «Diferencia» pasan a ser «Efectivo esperado» y
    «Dif. efectivo»;
  - columna nueva «QR»;
  - los cierres antiguos se marcan «fórmula anterior», con el tooltip de la
    diferencia registrada.
- **Totales del encabezado:** se calculan en la API sobre todo el filtro, no
  sobre la página (riesgo 7).

### 8.11 Relación con el «Resumen de caja» ya publicado

El bloque de `352d748` cuenta **lo registrado por el usuario** (sus pendientes
cobradas por otros suman a su caja). El arqueo cuenta **lo que entró a su
cajón**. Pueden diferir cuando hay pendientes cobradas por otra persona o de
otros días. Opciones:

- **(a)** dejar el bloque como está, con la nota que ya muestra;
- **(b)** alinearlo con el arqueo (una sola definición).

**Recomiendo (b)**, para que la pantalla, el reporte y el cierre digan lo mismo.
Esto cambia lo que aprobaste para ese bloque. **Decide.**

### 8.12 Ejemplo numérico completo

Cajero U, jornada 16/11. Cierra a las 20:00 y declara Bs 365. Un supervisor
valida a las 20:30.

| Mov. | Detalle | Categoría | Tipo pac. | Total | Efectivo | QR |
|---|---|---|---|---|---|---|
| V1 | Venta EFECTIVO 09:00 | FARMACIA | EXTERNO | 100 | 100 | 0 |
| V2 | Venta QR 10:00 | LABORATORIO | EXTERNO | 50 | 0 | 50 |
| V3 | Venta MIXTO 10:30 | SERVICIOS MÉDICOS | AMBULATORIO | 80 | 50 | 30 |
| V4 | Pendiente de U del 15/11, cobrada hoy 11:00 en efectivo | LABORATORIO | EXTERNO | 40 | 40 | 0 |
| V5 | Cobro de internación EFECTIVO 12:00 | SIN CATEGORÍA | INTERNO | 200 | 200 | 0 |
| G1 | Gasto 13:00 (cat. LIMPIEZA / ARTÍCULOS) | — | — | −20 | −20 | — |
| V6 | Venta EFECTIVO 14:00 (FARMACIA, ítem de lista 35 cobrado a 30), **anulada 15:00** | — | — | (30) | — | — |
| V7 | Pendiente de U del 16/11, sin cobrar | LABORATORIO | EXTERNO | (60) | — | — |

Declarado: 1×200 + 1×100 + 1×50 + 1×10 + 2×2 + 1×1 = **365**.

**1. Encabezado.** Cajero U · jornada 16/11/2026 · cerrado 20:00 · VALIDADO por
supervisor el 16/11 20:30.

**2. Arqueo de efectivo**

| | Bs |
|---|---|
| Efectivo de ventas (100 + 50 + 40 + 200) | 390.00 |
| (−) Gastos en efectivo | 20.00 |
| **Efectivo esperado** | **370.00** |
| Efectivo declarado (desglose arriba) | 365.00 |
| **Diferencia de efectivo** | **−5.00 (faltante)** |
| *Con la fórmula anterior habría sido* 365 − (470 − 20) | *−85.00* |

**3. Otros medios.** QR: Bs 80.00 en 2 transacciones (V2 y la parte QR de V3).
Transferencia y tarjeta: 0.

**4. Resumen de ventas** (5 ventas, Bs 470.00)

| 4a Forma de pago | Bs | 4b Categoría | Bs | 4c Tipo de paciente | Bs |
|---|---|---|---|---|---|
| EFECTIVO (V1, V4, V5) | 340 | FARMACIA | 100 | EXTERNO (V1, V2, V4) | 190 |
| QR (V2) | 50 | LABORATORIO | 90 | AMBULATORIO | 80 |
| MIXTO, parte efectivo | 50 | SERVICIOS MÉDICOS | 80 | INTERNO (internación) | 200 |
| MIXTO, parte QR | 30 | SIN CATEGORÍA | 200 | | |
| **Total** | **470** | **Total** | **470** | **Total** | **470** |

- 4d Cobros de internación: 1 · Bs 200 (efectivo).
- 4e Pendientes de otros días cobradas hoy: 1 · Bs 40 (V4, del 15/11).

**5. Gastos:** LIMPIEZA / ARTÍCULOS, 1 · Bs 20. Total Bs 20.

**6. Controles**
- Anuladas del día: 1 · Bs 30 (V6), antes del cierre. Después del cierre: 0.
- Precio modificado: ninguno en ventas vigentes. V6 tenía uno, pero está
  anulada: se informa en 6a.
- Pendientes sin cobrar: 1 · Bs 60 (V7).

**7. Detalle:** V1, V2, V3, V4, V5 y G1, por hora. Σ ingresos 470 − gasto 20 =
450.

**Cuadres:**
- 4a = 4b = 4c = 4f = **470** ✔
- Efectivo de 4a (340 + 50 = 390) − gastos (20) = **370** = esperado ✔
- QR de 4a (50 + 30) = **80** = sección 3 ✔
- Detalle 450 = 470 − 20 ✔
- Control del reparto: 390 + 80 + 0 = 470 ✔

**Anulación después del cierre** (variante): si el 16/11 a las 20:15 alguien
con «Anular Ventas Caja Cerrada» anula V1 (el cierre aún no está validado):

- se guarda `anulado_en`;
- `recalcular()` deja el esperado en **270** y la diferencia en **+95**;
- el reporte lista V1 en «Anuladas después del cierre».

Si el cierre ya estaba validado, la anulación da 422.

### 8.13 Riesgos

1. **Cambio para los cajeros:** desde el despliegue la diferencia es real
   (solo efectivo). Hay que avisar antes, porque los faltantes «de siempre» por
   QR desaparecen.
2. **Cierres antiguos recalculados al vuelo:** si se anularon ventas después de
   esos cierres, la diferencia recalculada no refleja lo que había en caja. Por
   eso se muestran **las dos** cifras y la nota.
3. **Bloqueo de anulaciones:** una venta mal cargada en una caja cerrada ya no
   se anula sin el permiso nuevo. Hay que dárselo a quien corresponda antes de
   desplegar.
4. **Migración del permiso:** agrega filas (no modifica). Ver §8.2.
5. **Internaciones TRANSFERENCIA o TARJETA:** no son efectivo ni QR. Si el
   negocio las cuenta como «QR», hay que decidirlo; hoy van a «Otros medios».
6. **Dos definiciones** (resumen de caja contra arqueo) si no se aprueba §8.11(b).
7. **Rendimiento:** el reporte hace unas 12 consultas por cierre. No hay
   problema, porque se pide de a uno.
8. **Fuentes del PDF:** usar «(-)» (Helvetica no tiene «−»).

### 8.14 Plan de pruebas

**Pest**, con su propio esquema SQLite, como `CobrarTodoTest`:

- **Fórmula:** el ejemplo de §8.12, que da esperado 370, diferencia −5, QR 80 y
  todos los cuadres de §8.9.
- **Internación:**
  - un cobro nuevo EFECTIVO, QR o TRANSFERENCIA guarda el reparto;
  - un cobro antiguo sin reparto se clasifica por `tipo_pago`.
- **Anulación:**
  - sin cierre, se anula;
  - con la caja cerrada y sin permiso, 422;
  - con el permiso, se anula, se recalcula y queda `anulado_en`;
  - con el cierre validado, 422 aun con el permiso.
- **`recalcular()`:** `GET` del listado, del detalle y de las exportaciones no
  cambian `updated_at` ni las cifras; `update` y `validar` sí.
- **Jornada anterior:**
  - el cajero cierra el día anterior antes de las 12:00;
  - después de las 12:00, 422;
  - el supervisor lo cierra en nombre del cajero;
  - con una fecha que no es la pendiente, 422.
- **Reporte:**
  - 403 sin cada permiso;
  - 422 con el cierre sin validar;
  - un cierre antiguo muestra las dos diferencias y la nota.
- **Permisos de montos:** el PDF del detalle, el voucher y el diálogo no traen
  montos sin «Ver Montos Caja».

**Manual, en `urme_prueba`** (migraciones con `--path`) y en SQLite de prueba:

- capturas del reporte (PDF y Excel), del diálogo, de la pantalla del cajero y
  del listado;
- un cierre antiguo y uno nuevo;
- un cierre con anulación posterior.

### 8.15 Decisiones pendientes antes de implementar

1. §8.11: alinear el «Resumen de caja» con el arqueo (recomendado) o dejarlo.
2. §8.7: el plazo para que el cajero cierre un día anterior (propuesta: 12:00
   del día siguiente) y el máximo de 2 días.
3. §8.4: `tipo_paciente = 'INTERNO'` en los cobros de internación.
4. §8.2: crear el permiso por migración (agrega filas) o desde la pantalla.
5. Riesgo 5: TRANSFERENCIA y TARJETA como «Otros medios» (propuesta) o dentro
   de QR.
6. Excel del reporte: una hoja por sección (propuesta) o solo PDF.

## 9. Implementación (2026-11-18)

Decisiones de §8.15: resumen de caja alineado con el arqueo; jornada anterior
hasta las 12:00 del día siguiente y como máximo 2 días; `tipo_paciente =
INTERNO` en los cobros de internación; permiso por migración; transferencia y
tarjeta como «Otros medios»; reporte **solo en PDF** (y su vista en pantalla).

| Parte | Dónde |
|---|---|
| Reparto por medio de pago y fórmula (fuente única) | `back/app/Services/ArqueoCaja.php` |
| Cierre, jornadas anteriores, recálculo solo en escrituras, resumen alineado, reporte | `CierreCajaController.php` (rutas nuevas: `GET cierres-caja/jornadas-pendientes`, `GET cierres-caja/{id}/reporte` y `/reporte/pdf`) |
| Reporte completo (pantalla y PDF) | `back/app/Services/ReporteCierreCaja.php`, `reportes/cierre-caja-reporte.blade.php` |
| Anulación con caja cerrada o validada; recálculo tras operar con tiempo autorizado | `VentaController.php` (`destroy`, `store`, `gasto`, `completar`) |
| Reparto en cobros de internación y en el cobro total del paciente | `CobroInternacion.php`, `InternacionController::pagarTotal`, `PacienteController::cobrarTodo` (tipo de pago validado: EFECTIVO, QR, TRANSFERENCIA, TARJETA) |
| Montos ocultos sin «Ver Montos Caja» | detalle (API), PDF y Excel del detalle, voucher |
| Pantallas | `cierres-caja/index.vue` (columnas, totales del filtro, reporte, jornadas sin cerrar), `CajaAcciones.vue` (arqueo del cajero, jornada pendiente), `addons/cierreCajaPrint.js` |
| Migraciones | `2026_11_18_000100` (cierres_caja), `000200` (ventas.anulado_*), `000300` (permiso). Clasificadas en `scripts/verificar_migraciones.php`; despliegue en `DESPLIEGUE_PRODUCCION.md`, anexo «Cierre de caja». |
| Tests | `tests/Feature/CierreCajaArqueoTest.php` (8 tests, plan §8.14); `CobrarTodoTest` con las columnas nuevas de su esquema mínimo |

**Diferencias con el diseño:**

- En los cierres anteriores (`formula = null`) corregir o validar **no** llena
  las columnas nuevas: siguen con la fórmula de siempre. La pantalla y el
  reporte los calculan al vuelo.
- La corrección de un cierre anterior sin autorización solo actualiza su
  diferencia contra el `monto_sistema` guardado, como antes.
- Los cobros de pendientes desde la cuenta del paciente (`cobrarTodo`) tampoco
  guardaban el reparto: ahora lo guardan, y los antiguos se clasifican por
  `tipo_pago` igual que las internaciones.
- Sin «Ver Montos Caja», el detalle también oculta los precios de los ítems, no
  solo los totales.

**Verificación:**

- Pest: 51 de 59. Pasan los 8 nuevos; los 8 que fallan son los fallos previos
  conocidos.
- Flujo completo por API sobre SQLite de prueba con el ejemplo §8.12:
  - el cierre en nombre del cajero da esperado 370, diferencia −5 y QR 80;
  - la fórmula anterior queda guardada aparte (450 / −85);
  - respuestas 403 y 422;
  - anulaciones bloqueadas;
  - los 6 cuadres del reporte dan CUADRA.
- La variante con anulación tras el cierre (esperado 270, diferencia +95) está
  en el test.
- En el ejemplo de prueba, la venta V4 se registró con precio de lista 50 y
  cobrada a 40, para que el control «precio modificado» tenga una fila.

