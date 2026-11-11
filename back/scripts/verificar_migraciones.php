<?php

/**
 * SOLO LECTURA. Uso, desde la carpeta back/ del servidor:
 *     php scripts/verificar_migraciones.php
 * Si se copia a otro lugar (p. ej. para correrlo antes de actualizar el código),
 * se corre igual desde la carpeta back/: php /tmp/verificar_migraciones.php
 * Ver docs/DESPLIEGUE_PRODUCCION.md.
 *
 * No ejecuta migraciones ni llama a up()/down(): solo lee archivos y hace SELECT
 * sobre la base configurada en el .env de ese servidor. Informa:
 *   1. las migraciones pendientes, marcadas ESQUEMA o DATOS según la auditoría
 *      del 2026-11-02, y un AVISO si hay alguna de DATOS pendiente sin neutralizar;
 *   2. si la migración de precios figura como ejecutada y en qué estado están
 *      los precios de su lista original (si el archivo aún la trae);
 *   3. si SODIO, POTASIO y CLORO ya tienen grupo_reporte;
 *   4. qué migraciones de datos están neutralizadas en este código.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Raíz de back/: la carpeta actual si tiene artisan; si no, la carpeta padre de scripts/.
$raiz = is_file(getcwd().'/artisan') ? getcwd() : dirname(__DIR__);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Auditoría (2026-11-02). ESQUEMA = solo crea/agrega estructura, sin tocar filas
 * existentes. ESQUEMA-DESTRUCTIVO = borra o cambia columnas. DATOS = inserta,
 * actualiza o borra filas (incluye las que rellenan una columna nueva y las de
 * permisos). Las neutralizadas se detectan por su comentario en el archivo.
 */
const CLASIFICACION = [
    '0001_01_01_000000_create_users_table' => ['ESQUEMA', 'crea users, password_reset_tokens, sessions'],
    '0001_01_01_000001_create_cache_table' => ['ESQUEMA', 'crea cache, cache_locks'],
    '0001_01_01_000002_create_jobs_table' => ['ESQUEMA', 'crea jobs, job_batches, failed_jobs'],
    '2026_06_28_084645_create_personal_access_tokens_table' => ['ESQUEMA', 'crea personal_access_tokens'],
    '2026_06_28_085220_create_permission_tables' => ['ESQUEMA', 'crea permissions, roles, model_has_permissions, model_has_roles, role_has_permissions'],
    '2026_06_28_085344_create_audits_table' => ['ESQUEMA', 'crea audits'],
    '2026_06_28_200000_create_fabricantes_table' => ['ESQUEMA', 'crea fabricantes'],
    '2026_06_28_200100_create_unidades_table' => ['ESQUEMA', 'crea unidades'],
    '2026_06_28_200200_create_productos_table' => ['ESQUEMA', 'crea productos'],
    '2026_06_28_201000_seed_inventory_catalogs_from_excel' => ['DATOS', 'inserta/actualiza fabricantes, unidades, productos'],
    '2026_06_28_201100_create_producto_inventarios_table' => ['DATOS', 'crea producto_inventarios e inserta filas desde productos'],
    '2026_06_30_090000_normalize_productos_tipo_to_farmacia' => ['DATOS', 'actualiza productos.tipo'],
    '2026_07_01_000100_create_tipo_productos_table' => ['ESQUEMA', 'crea tipo_productos'],
    '2026_07_01_000200_add_tipo_producto_and_precio_to_productos_table' => ['ESQUEMA', 'productos + tipo_producto_id, precio'],
    '2026_07_01_000300_seed_servicios_hospitalarios_productos' => ['DATOS', 'inserta tipo_productos y productos (servicios)'],
    '2026_07_02_000100_add_color_to_tipo_productos_table' => ['ESQUEMA', 'tipo_productos + color'],
    '2026_07_02_000200_backfill_farmacia_tipo_producto' => ['DATOS', 'inserta tipo_productos; actualiza productos.tipo_producto_id'],
    '2026_07_03_000100_diversify_tipo_producto_colors' => ['DATOS', 'actualiza tipo_productos.color'],
    '2026_07_03_000200_create_seguros_table' => ['ESQUEMA', 'crea seguros'],
    '2026_07_04_000100_create_pacientes_table' => ['ESQUEMA', 'crea pacientes'],
    '2026_07_04_000100_drop_tipo_from_productos_table' => ['ESQUEMA-DESTRUCTIVO', 'borra productos.tipo'],
    '2026_07_04_000200_create_internaciones_table' => ['ESQUEMA', 'crea internaciones'],
    '2026_07_05_000100_create_proveedores_table' => ['ESQUEMA', 'crea proveedores'],
    '2026_07_05_000200_create_compras_table' => ['ESQUEMA', 'crea compras'],
    '2026_07_05_000300_create_compra_detalles_table' => ['ESQUEMA', 'crea compra_detalles'],
    '2026_07_05_000400_add_compras_permissions' => ['DATOS', 'permisos de compras + admin'],
    '2026_07_05_000400_create_internacion_items_table' => ['ESQUEMA', 'crea internacion_items'],
    '2026_07_16_000100_create_ventas_table' => ['ESQUEMA', 'crea ventas'],
    '2026_07_16_000200_create_venta_detalles_table' => ['ESQUEMA', 'crea venta_detalles'],
    '2026_07_16_000300_add_ventas_permissions' => ['DATOS', 'permisos de ventas + admin'],
    '2026_07_16_000400_create_doctores_table' => ['DATOS', 'crea especialidades, doctores, doctor_especialidad e inserta especialidades'],
    '2026_07_16_000500_add_doctor_seguro_to_ventas_table' => ['ESQUEMA-DESTRUCTIVO', 'ventas + doctor_id, seguro_id; borra ventas.doctor'],
    '2026_07_16_000600_add_doctores_permissions' => ['DATOS', 'permisos de doctores + admin'],
    '2026_07_24_000100_add_lote_to_venta_detalles_table' => ['ESQUEMA', 'venta_detalles + lote'],
    '2026_07_24_000100_add_productos_vencimiento_permissions' => ['DATOS', 'permisos de vencimientos + admin'],
    '2026_07_24_020000_create_producto_laboratorio_configuracion_tables' => ['ESQUEMA', 'crea producto_laboratorio_datos, producto_laboratorio_formulas'],
    '2026_07_24_030000_seed_producto_laboratorio_configuracion' => ['DATOS', 'inserta producto_laboratorio_datos y formulas'],
    '2026_07_24_040000_seed_producto_laboratorio_formulas_adicionales' => ['DATOS', 'inserta/actualiza producto_laboratorio_formulas'],
    '2026_07_24_050000_link_producto_laboratorio_formulas_to_datos' => ['DATOS', 'formulas + columna; inserta datos y actualiza formulas'],
    '2026_07_24_060000_create_solicitudes_laboratorio_tables' => ['DATOS', 'crea solicitudes, items, resultados + permisos de laboratorio'],
    '2026_08_03_000100_add_es_laboratorio_to_tipo_productos_table' => ['ESQUEMA', 'tipo_productos + es_laboratorio'],
    '2026_08_03_000200_seed_laboratorio_tipos_producto' => ['DATOS', 'seeder: crea/actualiza tipo_productos y reubica productos.tipo_producto_id'],
    '2026_08_03_010000_create_producto_laboratorio_validaciones_table' => ['ESQUEMA', 'crea producto_laboratorio_validaciones'],
    '2026_08_03_020000_add_valor_defecto_y_opciones_to_laboratorio_datos' => ['ESQUEMA', 'producto_laboratorio_datos + columnas; crea producto_laboratorio_dato_opciones'],
    '2026_08_03_030000_add_precio_seguro_to_productos_table' => ['ESQUEMA', 'productos + precio_seguro'],
    '2026_08_03_040000_seed_arancel_laboratorio_oficial' => ['DATOS', 'crea productos de laboratorio (arancel)'],
    '2026_08_19_000100_add_modulo_to_permissions_table' => ['DATOS', 'permissions + modulo; actualiza permisos'],
    '2026_08_19_000200_add_productos_farmacia_permissions' => ['DATOS', 'permisos de farmacia + admin'],
    '2026_08_19_000300_add_fecha_nacimiento_to_pacientes_table' => ['ESQUEMA', 'pacientes + fecha_nacimiento'],
    '2026_08_19_000400_add_ver_montos_caja_permission' => ['DATOS', 'permiso + admin'],
    '2026_08_19_000500_create_cierres_caja_table' => ['ESQUEMA', 'crea cierres_caja'],
    '2026_08_19_000600_add_cierre_caja_permissions' => ['DATOS', 'permisos + admin'],
    '2026_08_19_000700_add_seguro_id_to_pacientes_table' => ['ESQUEMA', 'pacientes + seguro_id'],
    '2026_08_19_000800_add_cobro_to_ventas_table' => ['ESQUEMA', 'ventas + columnas de cobro'],
    '2026_08_19_000800_create_caja_movimientos_table' => ['ESQUEMA', 'crea caja_movimientos'],
    '2026_08_19_000900_add_cajas_permissions' => ['DATOS', 'permisos + admin'],
    '2026_08_19_000900_add_seguro_id_to_internaciones_table' => ['DATOS', 'internaciones + seguro_id y lo rellena desde pacientes'],
    '2026_08_19_001000_add_anulacion_to_caja_movimientos_table' => ['ESQUEMA', 'caja_movimientos + anulación'],
    '2026_08_19_001100_replace_eliminar_with_anular_cajas_permissions' => ['DATOS', 'reemplaza y borra permisos'],
    '2026_08_19_001200_add_metodo_y_muestra_to_laboratorio_datos' => ['ESQUEMA', 'datos y resultados de laboratorio + metodo, muestra'],
    '2026_08_19_001300_add_codigo_verificacion_to_solicitudes_table' => ['DATOS', 'solicitudes + codigo_verificacion y lo rellena'],
    '2026_09_02_000100_add_bloqueado_to_users_table' => ['ESQUEMA', 'users + bloqueado'],
    '2026_09_02_000200_add_descripcion_to_permissions_table' => ['DATOS', 'permissions + descripcion; actualiza permisos'],
    '2026_09_02_000300_add_seguimiento_seguro_to_internaciones_table' => ['ESQUEMA', 'internaciones + seguimiento de seguro'],
    '2026_09_02_000300_add_ver_detalle_ventas_permission' => ['DATOS', 'permiso + admin'],
    '2026_09_02_000400_add_pago_total_to_internaciones_table' => ['ESQUEMA', 'internaciones + pago_total'],
    '2026_09_02_000400_add_precio_original_to_ventas_tables' => ['DATOS', 'ventas/venta_detalles + *_original y las rellena'],
    '2026_09_02_000500_add_orden_to_solicitud_laboratorio_items_table' => ['ESQUEMA', 'solicitud_laboratorio_items + orden'],
    '2026_09_02_000600_create_derivaciones_table' => ['ESQUEMA', 'crea derivaciones'],
    '2026_09_02_000700_add_derivaciones_permissions' => ['DATOS', 'permisos + admin'],
    '2026_09_02_000800_create_reactivos_tables' => ['ESQUEMA', 'crea reactivos, servicio_laboratorio_reactivos'],
    '2026_09_02_000900_add_reactivos_permissions' => ['DATOS', 'permisos + admin'],
    '2026_09_02_001000_seed_reactivos_examples' => ['DATOS', 'inserta reactivos de ejemplo y vínculos'],
    '2026_09_10_000100_add_nombre_comercial_to_productos_table' => ['ESQUEMA', 'productos + nombre_comercial'],
    '2026_09_10_000200_add_tipo_movimiento_to_ventas_table' => ['ESQUEMA', 'ventas + tipo_movimiento'],
    '2026_09_10_000200_seed_inventario_inicial_farmacia' => ['DATOS', 'BORRA compras/compra_detalles; crea compra inicial, productos, fabricantes, unidades (la versión del repositorio está neutralizada desde 2026-11-03)'],
    '2026_09_10_000300_create_bajas_tables' => ['ESQUEMA', 'crea bajas, baja_detalles'],
    '2026_09_10_000400_add_bajas_permissions' => ['DATOS', 'permisos + admin'],
    '2026_09_10_000500_add_editar_lotes_farmacia_permission' => ['DATOS', 'permiso'],
    '2026_09_14_000100_change_rango_referencia_to_longtext' => ['ESQUEMA-DESTRUCTIVO', 'cambia tipo de rango_referencia (datos y resultados de laboratorio)'],
    '2026_09_17_000100_create_tipo_producto_padres_table' => ['DATOS', 'crea tipo_producto_padres; tipo_productos + columna; asigna padre a todos los tipos'],
    '2026_09_26_000100_add_montos_pago_to_ventas_table' => ['ESQUEMA', 'ventas + montos de pago'],
    '2026_09_26_000200_add_detalle_efectivo_to_cierres_caja_table' => ['ESQUEMA', 'cierres_caja + detalle de efectivo'],
    '2026_09_26_000300_actualizar_precios_lista_2026' => ['DATOS', 'actualiza productos.precio'],
    '2026_09_26_000300_add_validacion_to_cierres_caja_table' => ['ESQUEMA', 'cierres_caja + validación'],
    '2026_09_26_000300_add_ver_ventas_clientes_permission' => ['DATOS', 'permiso'],
    '2026_09_26_000400_add_validar_cierres_caja_permission' => ['DATOS', 'permiso + admin'],
    '2026_09_26_000500_add_ver_compras_pacientes_permission' => ['DATOS', 'permiso + admin'],
    '2026_09_26_000600_add_autorizacion_to_cierres_caja_table' => ['DATOS', 'cierres_caja + autorización; permiso + admin'],
    '2026_09_28_000100_reiniciar_inventario_oficial_farmacia' => ['DATOS', 'BORRA compras, bajas, ventas de farmacia y cierres; da de baja y recrea productos de farmacia (la versión del repositorio está neutralizada desde 2026-11-03)'],
    '2026_10_05_000100_actualizar_precios_oferta_servicios' => ['DATOS', 'actualiza productos.precio'],
    '2026_10_06_000100_actualizar_inventario_farmacia_con_precios' => ['DATOS', 'actualiza productos, compra_detalles, venta_detalles, baja_detalles'],
    '2026_10_07_000100_actualizar_precios_laboratorio' => ['DATOS', 'actualiza productos.precio de 247 exámenes (la versión del repositorio está neutralizada desde 2026-11-02)'],
    '2026_10_22_000100_add_grupo_reporte_to_productos_table' => ['ESQUEMA', 'productos + grupo_reporte, orden_reporte (nullable)'],
    '2026_10_22_000200_agrupar_electrolitos_en_informe_laboratorio' => ['DATOS', 'actualiza productos.grupo_reporte/orden_reporte de SODIO, POTASIO y CLORO (la versión del repositorio está neutralizada desde 2026-11-02)'],
    '2026_11_02_000100_add_codigo_laboratorio_to_solicitudes_table' => ['ESQUEMA', 'solicitudes + codigo_laboratorio (nullable, índice)'],
    '2026_11_04_000100_add_categoria_gasto_to_ventas_table' => ['ESQUEMA', 'ventas + tipo_producto_padre_id, tipo_producto_id (nullable, índices y claves foráneas)'],
    '2026_11_04_000200_add_categoria_gasto_to_caja_movimientos_table' => ['ESQUEMA', 'caja_movimientos + tipo_producto_padre_id, tipo_producto_id (nullable, índices y claves foráneas)'],
    '2026_11_11_000100_add_es_perfil_to_productos_table' => ['ESQUEMA', 'productos + es_perfil (boolean default false, índice)'],
    '2026_11_11_000200_create_producto_perfil_items_table' => ['ESQUEMA', 'crea producto_perfil_items (perfil_id, producto_id, orden; único y claves foráneas)'],
    '2026_11_12_000100_create_solicitud_laboratorio_perfiles_table' => ['ESQUEMA', 'crea solicitud_laboratorio_perfiles (vacía; claves foráneas a solicitudes y productos)'],
    '2026_11_12_000200_add_perfil_to_solicitud_laboratorio_items_table' => ['ESQUEMA', 'solicitud_laboratorio_items + solicitud_laboratorio_perfil_id (nullable, índice y clave foránea)'],
];

$base = $raiz.'/database/migrations';
$conexion = DB::connection();
echo 'Base: '.$conexion->getDatabaseName().' @ '.config('database.connections.'.$conexion->getName().'.host')."\n\n";

$ejecutadas = DB::table('migrations')->pluck('batch', 'migration');
$archivos = collect(glob("$base/*.php"))->map(fn ($f) => basename($f, '.php'))->sort()->values();
$neutralizada = fn (string $m) => str_contains((string) @file_get_contents("$base/$m.php"), 'NEUTRALIZADA A PROPÓSITO');

echo "== Migraciones pendientes (en el repositorio, no en la tabla migrations):\n";
$pendientes = $archivos->reject(fn ($m) => $ejecutadas->has($m));
$riesgo = [];
foreach ($pendientes as $m) {
    [$tipo, $detalle] = CLASIFICACION[$m] ?? ['SIN CLASIFICAR', 'revisar a mano antes de aplicar'];
    $marca = $tipo === 'DATOS' ? ($neutralizada($m) ? 'DATOS (neutralizada, sin efecto)' : 'DATOS') : $tipo;
    if (($tipo === 'DATOS' && ! $neutralizada($m)) || $tipo === 'SIN CLASIFICAR' || $tipo === 'ESQUEMA-DESTRUCTIVO') {
        $riesgo[] = $m;
    }
    echo "  - [$marca] $m\n      $detalle\n";
}
if ($pendientes->isEmpty()) {
    echo "  (ninguna)\n";
}
echo $riesgo
    ? "\n!!! AVISO: hay migraciones pendientes que modificarían datos o estructura existente (o sin clasificar):\n".implode("\n", array_map(fn ($m) => "!!!   $m", $riesgo))."\n!!! No ejecutar `php artisan migrate` sin --path.\n"
    : "\nOK: ninguna migración pendiente modifica datos existentes.\n";

echo "\n== Migraciones de DATOS neutralizadas en este código (up() y down() sin efecto):\n";
$neutralizadas = $archivos->filter($neutralizada);
foreach ($neutralizadas as $m) {
    echo "  - [DATOS (neutralizada, sin efecto)] $m: ".($ejecutadas->has($m) ? 'ya ejecutada (lote '.$ejecutadas[$m].')' : 'pendiente')."\n";
}
if ($neutralizadas->isEmpty()) {
    echo "  (ninguna: este código todavía no trae las migraciones neutralizadas)\n";
}

$precios = '2026_10_07_000100_actualizar_precios_laboratorio';
echo "\n== $precios: ".($ejecutadas->has($precios) ? 'YA EJECUTADA (lote '.$ejecutadas[$precios].')' : 'PENDIENTE')
    .($neutralizada($precios) ? ' — archivo neutralizado' : '')."\n";
$migracion = is_file("$base/$precios.php") ? require "$base/$precios.php" : null;
if ($migracion && method_exists($migracion, 'precios')) {
    $filas = (new ReflectionMethod($migracion, 'precios'))->invoke($migracion);
    $productos = DB::table('productos')->whereIn('id', array_column($filas, 0))->get(['id', 'nombre', 'precio', 'deleted_at'])->keyBy('id');
    $conteo = ['coincide id+nombre' => 0, 'no coincide' => 0, 'precio = anterior' => 0, 'precio = nuevo' => 0, 'precio = otro valor' => 0];
    foreach ($filas as [$id, $nombre, $anterior, $nuevo]) {
        $p = $productos[$id] ?? null;
        if (! $p || $p->deleted_at || $p->nombre !== $nombre) {
            $conteo['no coincide']++;

            continue;
        }
        $conteo['coincide id+nombre']++;
        $conteo[(float) $p->precio == $nuevo ? 'precio = nuevo' : ((float) $p->precio == $anterior ? 'precio = anterior' : 'precio = otro valor')]++;
    }
    echo '  Exámenes en la lista: '.count($filas)."\n";
    foreach ($conteo as $k => $v) {
        echo "  $k: $v\n";
    }
} else {
    echo "  (el archivo ya no trae la lista de precios; la original está en git, commit 2064e28)\n";
}

$electrolitos = '2026_10_22_000200_agrupar_electrolitos_en_informe_laboratorio';
echo "\n== $electrolitos: ".($ejecutadas->has($electrolitos) ? 'YA EJECUTADA (lote '.$ejecutadas[$electrolitos].')' : 'PENDIENTE')
    .($neutralizada($electrolitos) ? ' — archivo neutralizado' : '')."\n";
if (Schema::hasColumn('productos', 'grupo_reporte')) {
    $filas = DB::table('productos')
        ->whereRaw('UPPER(TRIM(nombre)) IN (?, ?, ?)', ['SODIO', 'POTASIO', 'CLORO'])
        ->get(['id', 'nombre', 'grupo_reporte', 'orden_reporte', 'deleted_at']);
    foreach ($filas as $f) {
        echo sprintf("  #%d %s: grupo_reporte=%s orden_reporte=%s%s\n", $f->id, $f->nombre,
            json_encode($f->grupo_reporte, JSON_UNESCAPED_UNICODE), json_encode($f->orden_reporte), $f->deleted_at ? ' (borrado)' : '');
    }
    echo '  Productos con grupo_reporte no vacío: '.DB::table('productos')->whereNotNull('grupo_reporte')->where('grupo_reporte', '<>', '')->count()."\n";
} else {
    echo "  La columna productos.grupo_reporte no existe todavía (falta 2026_10_22_000100_add_grupo_reporte_to_productos_table).\n";
}
