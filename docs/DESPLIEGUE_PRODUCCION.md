# Despliegue en producción

Guía para publicar en producción los cambios del informe de laboratorio y del
**código laboratorio** (rama `informe-laboratorio-encabezado`), junto con las
migraciones de datos neutralizadas que ya están en `master`.

> El sistema ya está configurado y funcionando en producción. Ningún paso de
> esta guía debe modificar datos existentes: solo se aplican migraciones de
> esquema **aditivas** (columnas nuevas, nullable).

---

## Regla permanente

**En producción nunca se ejecuta `php artisan migrate`, `migrate:fresh`,
`migrate:refresh`, `migrate:reset` ni `db:seed` sin `--path`.**

Motivo: de las 96 migraciones de `back/database/migrations`, **45 son de
datos** (insertan, actualizan o borran filas) y 3 cambian o borran columnas.
Cuatro de las de datos ya están neutralizadas en el repositorio (ver más
abajo), pero **41 siguen activas**, y un servidor con una versión anterior del
código todavía tiene también las cuatro originales. Si alguna estuviera
pendiente en producción, un `migrate` sin `--path` la ejecutaría junto con las
demás. Entre ellas hay migraciones que:

- **borraban inventario y ventas** (neutralizadas desde 2026-11-03, pero activas
  en cualquier copia del código anterior a esa fecha):
  `2026_09_10_000200_seed_inventario_inicial_farmacia` y
  `2026_09_28_000100_reiniciar_inventario_oficial_farmacia` (borran compras,
  bajas, ventas de farmacia y cierres de caja, y recrean los productos de farmacia);
- **cambian precios**: `2026_09_26_000300_actualizar_precios_lista_2026`,
  `2026_10_05_000100_actualizar_precios_oferta_servicios`,
  `2026_10_06_000100_actualizar_inventario_farmacia_con_precios`;
- cargan o cambian catálogos, permisos y agrupaciones.

`migrate:fresh`, `migrate:refresh` y `migrate:reset` además **borran o
revierten tablas completas**. `db:seed` vuelve a cargar catálogos y usuarios.

Cada migración se aplica **de a una**, con `--path`, y siempre con `--pretend`
antes para ver el SQL exacto que va a ejecutar:

```bash
php artisan migrate --pretend --path=database/migrations/<archivo>.php
php artisan migrate          --path=database/migrations/<archivo>.php
```

Cuatro migraciones de datos están **neutralizadas** (su `up()` y `down()` no
hacen nada), así que aunque figuren como pendientes no cambian nada. El script
de verificación las muestra como **DATOS (neutralizada, sin efecto)**:

| Migración | Qué hacía | Neutralizada | Versión original |
|---|---|---|---|
| `2026_09_10_000200_seed_inventario_inicial_farmacia` | Borraba compras y cargaba el inventario inicial de farmacia | 2026-11-03 | `143a350` |
| `2026_09_28_000100_reiniciar_inventario_oficial_farmacia` | Borraba compras, bajas, ventas de farmacia y cierres de caja; recreaba productos de farmacia | 2026-11-03 | `87bcc39` |
| `2026_10_07_000100_actualizar_precios_laboratorio` | Cambiaba el precio de 247 exámenes | 2026-11-02 | `2064e28` |
| `2026_10_22_000200_agrupar_electrolitos_en_informe_laboratorio` | Asignaba `grupo_reporte` a SODIO, POTASIO y CLORO | 2026-11-02 | `09d43a6` |

Consecuencia para entornos nuevos: en una instalación desde cero (base vacía),
`2026_10_06_000100_actualizar_inventario_farmacia_con_precios` falla, porque
espera los productos que creaba el reinicio del 28-09. No afecta a producción,
donde esos productos ya existen.

---

## Qué incluye este despliegue

| Parte | Cambio |
|---|---|
| Base de datos | `2026_10_22_000100_add_grupo_reporte_to_productos_table` (si está pendiente), `2026_11_02_000100_add_codigo_laboratorio_to_solicitudes_table`, `2026_11_04_000100_add_categoria_gasto_to_ventas_table`, `2026_11_04_000200_add_categoria_gasto_to_caja_movimientos_table`, `2026_11_11_000100_add_es_perfil_to_productos_table`, `2026_11_11_000200_create_producto_perfil_items_table`, `2026_11_12_000100_create_solicitud_laboratorio_perfiles_table` y `2026_11_12_000200_add_perfil_to_solicitud_laboratorio_items_table`. Todas son de esquema y aditivas: columnas nullable o con valor por defecto, y una tabla nueva vacía. |
| Backend | Informe de laboratorio (firma, dirección, CODELAB, datos del paciente en 3 columnas), campo código laboratorio obligatorio en solicitudes nuevas, código compuesto `LAB-AAAAMMDD-00000-<código>` en informes y pantallas. Categoría (obligatoria) y subcategoría de los gastos de Ventas y de las dos cajas, con `GET /categorias-gasto`. Perfiles de exámenes, Fases 1, 2 y 4 (catálogo, venta y expansión en la solicitud, reporte general, informe agrupado por perfil), **apagados** con `LAB_PERFILES_HABILITADO=false`: el sistema funciona como antes. Ya rigen siempre: no se puede dar de baja un examen que forma parte de un perfil, los perfiles no se cargan a una internación y no aparecen en Farmacia → productos (no hay perfiles hasta que se encienda). |
| `.env` del backend | 6 variables nuevas (paso 8). |
| Frontend | Campo "Código laboratorio" en Crear laboratorio, código compuesto en listado, WhatsApp, verificación y reporte general. Categoría y subcategoría en "Nuevo gasto" de Ventas y en los gastos de las cajas. Corrección de la hora al editar movimientos de caja (antes se corría +4 h). **Se publica en el mismo despliegue** (paso 10). |
| Reactivos | Unidades µL, DETERMINACIÓN y TACO en «Nuevo reactivo»; el signo micro se guarda siempre como U+00B5 y no pasa a mayúsculas (rama `reactivos-unidades`, integrada en `master` en `1484a76`). Sin migraciones ni cambios de datos. Según el equipo ya está en producción: se incluye para que el despliegue de `master` no lo revierta. |
| Dependencias | No hay cambios en `composer.lock` ni en `pnpm-lock.yaml`. |

Todo esto ya está en `master`: se despliega `master`.

---

## Antes de empezar

- Elegir una ventana con poco movimiento: el sistema quedará en mantenimiento
  unos minutos (pasos 4 a 9).
- Tener a mano: acceso a la carpeta `back/` del servidor, usuario y contraseña
  de la base, y el frontend ya compilado (paso 10.1 se puede hacer antes).
- Avisar al personal de la hora del corte y de que tendrá que cerrar y volver a
  abrir la aplicación (paso 11).

---

## 1. Respaldo completo de la base

Guardar el respaldo **fuera** de la carpeta del sistema y comprobar que el
archivo termina con la línea `-- Dump completed`. Guardar también una copia de
`back/.env`.

**Linux**

```bash
mysqldump --single-transaction --routines --triggers --events \
  -u USUARIO -p NOMBRE_BASE > ~/respaldos/urme_$(date +%Y%m%d_%H%M).sql
tail -n 1 ~/respaldos/urme_*.sql      # debe decir: -- Dump completed ...
cp back/.env ~/respaldos/env_$(date +%Y%m%d_%H%M).bak
```

**Windows / XAMPP** (cmd; ajustar `C:\xampp` si XAMPP está en otra carpeta)

```bat
REM Fecha y hora con formato de Windows en español (dd/mm/aaaa): AAAAMMDD_HHMM
set HORA=%time: =0%
set FECHA=%date:~6,4%%date:~3,2%%date:~0,2%_%HORA:~0,2%%HORA:~3,2%
mkdir C:\respaldos 2>nul

"C:\xampp\mysql\bin\mysqldump.exe" --single-transaction --routines --triggers --events ^
  -u USUARIO -p NOMBRE_BASE > C:\respaldos\urme_%FECHA%.sql

powershell -Command "Get-Content C:\respaldos\urme_%FECHA%.sql -Tail 1"
copy back\.env C:\respaldos\env_%FECHA%.bak
```

Si `%date%` tiene otro formato en ese servidor (por ejemplo `mm/dd/aaaa`), usar
PowerShell para la fecha:

```powershell
$FECHA = Get-Date -Format yyyyMMdd_HHmm
& "C:\xampp\mysql\bin\mysqldump.exe" --single-transaction --routines --triggers --events `
  -u USUARIO -p NOMBRE_BASE --result-file="C:\respaldos\urme_$FECHA.sql"
```

---

## 2. Foto "antes" de precios y agrupaciones (solo lectura)

Ejecutar en phpMyAdmin o en el cliente `mysql` y **guardar el resultado**
(copiar a un archivo de texto). Se repite en el paso 12 para comparar.

```sql
-- Huella de todos los precios (cambia si cambia cualquier precio de cualquier producto)
SELECT COUNT(*) AS productos, SUM(precio) AS suma_precio, SUM(precio_seguro) AS suma_precio_seguro,
       BIT_XOR(CRC32(CONCAT_WS('|', id, precio, IFNULL(precio_seguro, ''), IFNULL(deleted_at, '')))) AS huella_precios
FROM productos;

-- Muestra de precios para comparar a ojo
SELECT id, nombre, precio, precio_seguro
FROM productos
WHERE UPPER(TRIM(nombre)) IN ('SODIO', 'POTASIO', 'CLORO',
      'HEMOGRAMA (INDICES HEMATIMÉTRICOS + PLAQUETAS)', 'PAP - PAPANICOLAU', 'GLICEMIA EN AYUNAS')
ORDER BY id;
```

Solo si la columna `grupo_reporte` **ya existe** en producción (si no existe,
estas dos consultas dan error; es normal y se omiten):

```sql
SELECT COUNT(*) AS con_grupo FROM productos WHERE grupo_reporte IS NOT NULL AND grupo_reporte <> '';
SELECT BIT_XOR(CRC32(CONCAT_WS('|', id, IFNULL(grupo_reporte, ''), IFNULL(orden_reporte, '')))) AS huella_grupos
FROM productos;
```

---

## 3. Verificación con el código actual (solo lectura)

Antes de actualizar el código, copiar el script desde el repositorio nuevo
(`back/scripts/verificar_migraciones.php`) a una carpeta temporal del servidor
y correrlo **desde la carpeta `back/`**. Así muestra el estado real con los
archivos de migración que producción tiene hoy.

```bash
cd /ruta/a/urme/back
php /tmp/verificar_migraciones.php           # Windows: php C:\temp\verificar_migraciones.php
```

Guardar la salida. Muestra:

- cada migración pendiente marcada **ESQUEMA**, **ESQUEMA-DESTRUCTIVO**,
  **DATOS** o **DATOS (neutralizada, sin efecto)**;
- un aviso `!!! AVISO` si hay alguna pendiente que modificaría datos o
  estructura existente;
- si la migración de precios figura como ejecutada y, si el archivo todavía
  trae su lista, en qué estado están esos precios;
- el `grupo_reporte` de SODIO, POTASIO y CLORO (para saber si la migración de
  electrolitos ya se aplicó allí);
- la lista de migraciones neutralizadas que trae ese código, con su estado
  (ya ejecutada o pendiente).

Con el código actual de producción, las cuatro migraciones de la tabla de
arriba todavía son las originales: si alguna figura pendiente allí, el script
dará el aviso y la lista de neutralizadas saldrá vacía. Es esperable. **No se
ejecuta ninguna migración en este paso**, y anotar cuáles de esas cuatro
aparecen pendientes.

---

## 4. Modo mantenimiento

```bash
php artisan down
```

Desde aquí la API responde 503 y nadie puede trabajar. El sistema vuelve en el
paso 9.

---

## 5. Actualizar el código del backend

Actualizar `back/` con `master` (por ejemplo `git pull origin master`). No hace
falta `composer install`: no cambiaron dependencias.

---

## 6. Verificación con el código nuevo

```bash
php scripts/verificar_migraciones.php
php artisan migrate:status
```

Resultado esperado: solo quedan pendientes migraciones **ESQUEMA** y, si las
hubiera, alguna de las cuatro **neutralizadas**, y el script dice `OK: ninguna migración pendiente modifica
datos existentes.`

**Si aparece `!!! AVISO`, detenerse**: no aplicar migraciones, volver a la
versión anterior del código y `php artisan up`.

---

## 7. Migraciones de esquema, de a una

**7.1 Agrupación del informe** (solo si figura pendiente en el paso 6):

```bash
php artisan migrate --pretend --path=database/migrations/2026_10_22_000100_add_grupo_reporte_to_productos_table.php
```

Debe mostrar solo:

```
alter table `productos` add `grupo_reporte` varchar(100) null after `tipo_producto_id`
alter table `productos` add `orden_reporte` int unsigned null after `grupo_reporte`
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_10_22_000100_add_grupo_reporte_to_productos_table.php
```

**7.2 Código laboratorio:**

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_02_000100_add_codigo_laboratorio_to_solicitudes_table.php
```

Debe mostrar solo:

```
alter table `solicitudes` add `codigo_laboratorio` varchar(20) null after `codigo_verificacion`
alter table `solicitudes` add index `solicitudes_codigo_laboratorio_index`(`codigo_laboratorio`)
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_02_000100_add_codigo_laboratorio_to_solicitudes_table.php
```

**7.3 Categoría de gasto en ventas:**

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_04_000100_add_categoria_gasto_to_ventas_table.php
```

Debe mostrar solo:

```
alter table `ventas` add `tipo_producto_padre_id` bigint unsigned null after `tipo_movimiento`
alter table `ventas` add `tipo_producto_id` bigint unsigned null after `tipo_producto_padre_id`
alter table `ventas` add index `ventas_tipo_producto_padre_id_index`(`tipo_producto_padre_id`)
alter table `ventas` add index `ventas_tipo_producto_id_index`(`tipo_producto_id`)
alter table `ventas` add constraint `ventas_tipo_producto_padre_id_foreign` foreign key (`tipo_producto_padre_id`) references `tipo_producto_padres` (`id`) on delete restrict on update cascade
alter table `ventas` add constraint `ventas_tipo_producto_id_foreign` foreign key (`tipo_producto_id`) references `tipo_productos` (`id`) on delete restrict on update cascade
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_04_000100_add_categoria_gasto_to_ventas_table.php
```

**7.4 Categoría de gasto en caja_movimientos:**

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_04_000200_add_categoria_gasto_to_caja_movimientos_table.php
```

Debe mostrar solo:

```
alter table `caja_movimientos` add `tipo_producto_padre_id` bigint unsigned null after `categoria`
alter table `caja_movimientos` add `tipo_producto_id` bigint unsigned null after `tipo_producto_padre_id`
alter table `caja_movimientos` add index `caja_movimientos_tipo_producto_padre_id_index`(`tipo_producto_padre_id`)
alter table `caja_movimientos` add index `caja_movimientos_tipo_producto_id_index`(`tipo_producto_id`)
alter table `caja_movimientos` add constraint `caja_movimientos_tipo_producto_padre_id_foreign` foreign key (`tipo_producto_padre_id`) references `tipo_producto_padres` (`id`) on delete restrict on update cascade
alter table `caja_movimientos` add constraint `caja_movimientos_tipo_producto_id_foreign` foreign key (`tipo_producto_id`) references `tipo_productos` (`id`) on delete restrict on update cascade
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_04_000200_add_categoria_gasto_to_caja_movimientos_table.php
```

Las ventas y movimientos existentes quedan sin categoría (`NULL`): no se
rellena nada. La columna de texto `caja_movimientos.categoria` no se toca.

**7.5 Perfiles de exámenes: marca en productos (obligatoria).** Aunque los
perfiles queden apagados, el código nuevo consulta `productos.es_perfil` en el
listado de productos, en "Crear laboratorio", en la venta y al dar de baja un
producto: **sin esta migración y la siguiente esas pantallas fallan**.

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_11_000100_add_es_perfil_to_productos_table.php
```

Debe mostrar solo:

```
alter table `productos` add `es_perfil` tinyint(1) not null default '0' after `precio_seguro`
alter table `productos` add index `productos_es_perfil_index`(`es_perfil`)
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_11_000100_add_es_perfil_to_productos_table.php
```

Todos los productos existentes quedan con `es_perfil = 0`.

**7.6 Perfiles de exámenes: composición (obligatoria):**

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_11_000200_create_producto_perfil_items_table.php
```

Debe mostrar solo:

```
create table `producto_perfil_items` (`id` bigint unsigned not null auto_increment primary key, `perfil_id` bigint unsigned not null, `producto_id` bigint unsigned not null, `orden` int unsigned not null default '0', `created_at` timestamp null, `updated_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `producto_perfil_items` add unique `producto_perfil_items_perfil_producto_unique`(`perfil_id`, `producto_id`)
alter table `producto_perfil_items` add index `producto_perfil_items_producto_id_index`(`producto_id`)
alter table `producto_perfil_items` add constraint `producto_perfil_items_perfil_id_foreign` foreign key (`perfil_id`) references `productos` (`id`) on delete restrict on update cascade
alter table `producto_perfil_items` add constraint `producto_perfil_items_producto_id_foreign` foreign key (`producto_id`) references `productos` (`id`) on delete restrict on update cascade
```

(El juego de caracteres de la primera línea puede variar según la configuración
de la base.) Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_11_000200_create_producto_perfil_items_table.php
```

La tabla queda vacía: los perfiles se crean desde la pantalla, nunca por
migración.

**7.7 Perfiles en la solicitud (obligatoria).** Aunque los perfiles queden
apagados, el código nuevo carga los perfiles de cada solicitud (listado, detalle,
reporte general): **sin esta migración y la siguiente esas pantallas fallan**.

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_12_000100_create_solicitud_laboratorio_perfiles_table.php
```

Debe mostrar solo:

```
create table `solicitud_laboratorio_perfiles` (`id` bigint unsigned not null auto_increment primary key, `solicitude_id` bigint unsigned not null, `perfil_producto_id` bigint unsigned null, `nombre` varchar(255) not null, `precio` decimal(10, 2) not null default '0', `orden` int unsigned not null default '0', `created_at` timestamp null, `updated_at` timestamp null, `deleted_at` timestamp null) default character set utf8mb4 collate 'utf8mb4_unicode_ci'
alter table `solicitud_laboratorio_perfiles` add index `slp_solicitude_id_index`(`solicitude_id`)
alter table `solicitud_laboratorio_perfiles` add index `slp_perfil_producto_id_index`(`perfil_producto_id`)
alter table `solicitud_laboratorio_perfiles` add constraint `slp_solicitude_id_foreign` foreign key (`solicitude_id`) references `solicitudes` (`id`) on delete cascade
alter table `solicitud_laboratorio_perfiles` add constraint `slp_perfil_producto_id_foreign` foreign key (`perfil_producto_id`) references `productos` (`id`) on delete set null
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_12_000100_create_solicitud_laboratorio_perfiles_table.php
```

**7.8 Perfil de cada examen de la solicitud (obligatoria):**

```bash
php artisan migrate --pretend --path=database/migrations/2026_11_12_000200_add_perfil_to_solicitud_laboratorio_items_table.php
```

Debe mostrar solo:

```
alter table `solicitud_laboratorio_items` add `solicitud_laboratorio_perfil_id` bigint unsigned null after `producto_id`
alter table `solicitud_laboratorio_items` add index `sli_perfil_id_index`(`solicitud_laboratorio_perfil_id`)
alter table `solicitud_laboratorio_items` add constraint `sli_perfil_id_foreign` foreign key (`solicitud_laboratorio_perfil_id`) references `solicitud_laboratorio_perfiles` (`id`) on delete set null
```

Si coincide:

```bash
php artisan migrate --path=database/migrations/2026_11_12_000200_add_perfil_to_solicitud_laboratorio_items_table.php
```

Todos los exámenes existentes quedan con `solicitud_laboratorio_perfil_id = NULL`
(exámenes sueltos): no cambia ningún precio ni total.

**7.9 Migraciones neutralizadas:** pueden quedar pendientes sin riesgo. Si se
quiere limpiar `migrate:status`, se registran igual con `--path`, solo después
de que su `--pretend` no muestre **ninguna** consulta.

Si un `--pretend` muestra algo distinto de lo indicado, **no ejecutar** y
revisar antes de seguir.

---

## 8. Variables nuevas de `.env` y caché de configuración

Agregar al `back/.env` de producción:

```
LAB_CODELAB=000043
LAB_HORARIO="24 horas"
LAB_DIRECCION_1="Calle Cochabamba"
LAB_DIRECCION_2="entre Soria Galvarro y 6 de Octubre"
LAB_CELULAR=63944238
LAB_PERFILES_HABILITADO=false
```

Las cinco primeras se leen desde `config/laboratorio.php` y se imprimen en el
encabezado del informe. `LAB_PERFILES_HABILITADO` se despliega en **`false`**:
los perfiles se encienden después, en un paso aparte y cuando el laboratorio lo
decida (ver «Encender los perfiles en producción», más abajo). Con `false` no se ve nada de
perfiles, no se pueden vender ni pedir, y sus endpoints responden 404; si faltara
la variable, también vale `false`. Después limpiar la caché:

```bash
php artisan config:clear
php artisan view:clear
# solo si producción usa caché de configuración:
php artisan config:cache
```

Sin este paso el informe no muestra la dirección ni el CODELAB nuevos.

### Encender los perfiles en producción

**Cuándo:** con la Fase 4 (informe agrupado por perfil) ya desplegada —es decir,
con este código en producción y los pasos 7.5 a 7.8 aplicados—, cuando el
laboratorio decida empezar a vender perfiles. No hace falta mantenimiento ni
migraciones.

**Antes de encender:**

1. Que estén en producción las migraciones de perfiles: `php artisan migrate:status`
   muestra como `Ran` las cuatro de `2026_11_11_*` y `2026_11_12_*`.
2. Que el personal sepa crear un perfil (Laboratorio → Catálogo de pruebas →
   Administrar → Tipo: Perfil) y que los perfiles **no se cargan a una internación**.
3. Un respaldo de la base (como el paso 1).

**Pasos:**

1. En el `back/.env` de producción: `LAB_PERFILES_HABILITADO=true`.
2. `php artisan config:clear` (y `php artisan config:cache` si producción usa
   caché de configuración).
3. Los usuarios recargan la página (o cierran y abren sesión): la configuración
   llega con `/me` y `/login`.

**Comprobaciones inmediatas** (con un usuario administrador):

1. Laboratorio → Catálogo de pruebas: aparecen el filtro «Tipo» y, en cada prueba,
   el selector «Tipo: Examen / Perfil».
2. Crear un perfil de prueba con dos exámenes (p. ej. SODIO y POTASIO, precio
   simbólico) y comprobar que **no se puede dar de baja** uno de sus exámenes.
3. «Nueva venta»: el perfil aparece con el chip PERFIL. «Crear laboratorio»: el
   perfil se ve como un bloque con sus exámenes. **No guardar ventas de prueba
   en producción**: basta ver que aparecen. El perfil de prueba se puede quitar
   (desmarcar «Perfil») o dar de baja después.
4. Abrir el PDF de una solicitud **anterior** (sin perfiles): debe verse igual que
   antes de encender.

### Interruptor de perfiles: una vez encendido, no volver a `false`

Cuando se pase `LAB_PERFILES_HABILITADO` a `true` y se **vendan perfiles en
producción** (o se creen solicitudes con perfiles), el interruptor **no debe
volver a `false`**. Con el interruptor apagado:

- las solicitudes que ya tienen perfiles **no se pueden modificar** (la API
  responde 422): no se les pueden cargar resultados hasta volver a encenderlo;
- una venta pagada con un perfil **no se puede cargar** en "Crear laboratorio"
  (el perfil no está en la lista), así que su solicitud no se podría crear;
- los perfiles dejan de venderse y desaparecen del catálogo, aunque sigan en las
  ventas y solicitudes ya hechas.

Ver e imprimir esas solicitudes, el reporte general y la caja siguen funcionando,
pero el trabajo diario del laboratorio quedaría trabado. Si hubiera que apagarlo
por un problema, antes hay que terminar (resultados cargados) todas las
solicitudes con perfiles que estén en `CREADO`.

---

## 9. Salir del modo mantenimiento

Solo después de los pasos 7 y 8:

```bash
php artisan up
```

---

## 10. Frontend (en el mismo despliegue)

El backend nuevo exige el código laboratorio en las solicitudes nuevas, así
que **el frontend nuevo tiene que publicarse en este mismo despliegue**.

10.1 Subir la versión en `front/.env.production` (por ejemplo de `1.0.0` a
`1.0.1`) y revisar que `VITE_API_BACK` apunte a la API de producción. Luego:

```bash
cd front
pnpm install
pnpm exec quasar build -m pwa
```

El resultado queda en `front/dist/pwa` (versionado en git). Este paso se puede
hacer antes del corte.

10.2 Publicar `front/dist/pwa` en el servidor web, reemplazando la versión
anterior. Conviene hacerlo durante el mantenimiento, antes del paso 9.

10.3 Comprobar en un navegador (ventana privada) que abajo del menú lateral se
lee la versión nueva, por ejemplo **"URME v1.0.1"**.

---

## 11. Avisar al personal

Mensaje sugerido:

> Se actualizó el sistema URME. Cierren **todas** las pestañas o ventanas de
> URME y vuelvan a abrirlo. Abajo del menú lateral debe decir **URME v1.0.1**.
> Si sigue diciendo la versión anterior, ciérrenlo y ábranlo una vez más (o
> presionen Ctrl+F5).

La aplicación es una PWA: el navegador guarda una copia de la versión anterior.
El service worker nuevo se activa en cuanto lo detecta, pero una pestaña que ya
estaba abierta sigue con el código viejo en memoria hasta que se recarga.

### Si un usuario sigue con la versión anterior

- Al **crear** un laboratorio, el sistema lo rechaza con el mensaje *"El código
  laboratorio es obligatorio."*, y la pantalla vieja no tiene ese campo para
  completarlo.
- Al **editar** una solicitud que ya tiene código laboratorio, también se
  rechaza por el mismo motivo.
- Editar solicitudes antiguas sin código sigue funcionando.
- No se pierde ni se guarda nada a medias: la solicitud simplemente no se crea.

Cómo resolverlo, en este orden:

1. Cerrar todas las pestañas o ventanas de URME y volver a abrir.
2. Recargar forzando: Ctrl+F5 (Ctrl+Shift+R en algunos navegadores).
3. Si está instalada como aplicación, cerrar la ventana de la aplicación por
   completo y volver a abrirla.
4. Último recurso: en el navegador, F12 → Aplicación (Application) → Service
   workers → *Unregister*, y en Almacenamiento → *Borrar datos del sitio*;
   luego recargar. Esto obliga a iniciar sesión otra vez.

---

## 12. Comprobaciones después del despliegue

1. Repetir las consultas del **paso 2** y comparar con lo guardado:
   `productos`, `suma_precio`, `suma_precio_seguro` y `huella_precios` deben
   ser **idénticos**; la muestra de precios, igual. Si `grupo_reporte` ya
   existía, `con_grupo` y `huella_grupos` también deben ser idénticos. Si la
   columna se creó en el paso 7.1, `con_grupo` debe ser **0**.
2. `php scripts/verificar_migraciones.php` → `OK`, con solo las neutralizadas
   pendientes (si no se registraron en 7.9).
3. Prueba funcional:
   - crear un laboratorio con código laboratorio (por ejemplo `A19`) y abrir su
     PDF: CODELAB encima, código `LAB-…-A19`, dirección bajo el logo, datos del
     paciente en 3 columnas y la firma con su espacio;
   - abrir el PDF de una solicitud anterior: el código sin sufijo;
   - escanear el QR de un informe: la página de verificación abre y muestra el
     código;
   - en "Laboratorios creados", buscar por el código laboratorio;
   - registrar un gasto en Ventas y otro en Caja General con categoría y
     subcategoría, y comprobar que el listado y el comprobante las muestran; los
     gastos anteriores deben verse como "Sin categoría" (o "Sin categoría
     (TEXTO)" en las cajas);
   - editar un movimiento de caja sin tocar la fecha: la hora no debe cambiar;
   - en Laboratorio → Catálogo de pruebas: no debe aparecer el filtro "Tipo" ni
     el selector "Examen / Perfil" (perfiles apagados), y el listado, "Nueva
     venta" y "Crear laboratorio" deben cargar normalmente (si fallan con
     `es_perfil` o con `solicitud_laboratorio_perfiles`, faltó alguno de los
     pasos 7.5 a 7.8).
4. **Categoría para gastos generales (decisión de la administración).** Las
   categorías de gasto son las de los tipos de producto (Laboratorio, Farmacia,
   Servicios médicos, Hospitalización, Imagenología), y ninguna sirve para gastos
   generales como administración, limpieza o refrigerios. Después del despliegue,
   la administración debe decidir si crea una desde la pantalla de tipos de
   producto. **No se crea por migración.** Tenga en cuenta que esa categoría
   aparecerá también entre las categorías de productos.

---

## Si hay que volver atrás

- **Código:** volver a la versión anterior del backend y del frontend
  (`front/dist/pwa` anterior), y repetir el paso 11.
- **Esquema:** las columnas nuevas no molestan a la versión anterior y pueden
  quedarse. Si hubiera que quitar la de código laboratorio:
  `php artisan migrate:rollback --path=database/migrations/2026_11_02_000100_add_codigo_laboratorio_to_solicitudes_table.php`
  (borra los códigos laboratorio cargados desde el despliegue). Lo mismo con las
  de categoría de gasto (`2026_11_04_000200_…` primero y luego
  `2026_11_04_000100_…`), que borran las categorías cargadas en los gastos.
  Las de perfiles (`2026_11_12_000200_…`, `2026_11_12_000100_…`,
  `2026_11_11_000200_…` y `2026_11_11_000100_…`, en ese orden)
  solo se revierten **después** de volver al código anterior: el código nuevo
  las necesita. Si los perfiles nunca se encendieron, no tienen datos que
  perder; si ya se usaron, revertirlas borra los perfiles de las solicitudes y
  su composición (no hacerlo sin respaldo).
- **Datos:** si las comprobaciones del paso 12 muestran cambios, restaurar el
  respaldo del paso 1.

---

## Anexo: `back/scripts/verificar_migraciones.php`

Script de **solo lectura**: no ejecuta migraciones ni llama a `up()`/`down()`;
solo lee los archivos de migración y hace consultas `SELECT` sobre la base del
`.env`.

```bash
cd back
php scripts/verificar_migraciones.php
```

También funciona copiado a otra carpeta, siempre que se ejecute desde `back/`
(así se usa en el paso 3, antes de actualizar el código).

La clasificación de cada migración (ESQUEMA, ESQUEMA-DESTRUCTIVO o DATOS) está
dentro del script, en la constante `CLASIFICACION`. **Al agregar una migración
nueva hay que clasificarla allí**; si no, el script la muestra como
`SIN CLASIFICAR` y da el aviso.
