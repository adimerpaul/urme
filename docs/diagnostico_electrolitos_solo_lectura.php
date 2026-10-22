<?php

/**
 * Diagnóstico del catálogo de electrolitos y perfiles de laboratorio — SOLO LECTURA.
 *
 * Uso (en el servidor, ANTES de desplegar):
 *     php diagnostico_electrolitos_solo_lectura.php /ruta/al/proyecto/back > diagnostico.md
 *
 * Garantías:
 * - Abre la sesión de MySQL en modo READ ONLY: cualquier escritura es rechazada por el servidor.
 * - Solo ejecuta SELECT. No muestra datos de pacientes: solo conteos e ID/código de solicitud.
 * - Usa la conexión configurada en el .env de esa instalación.
 */

use Illuminate\Support\Facades\DB;

$back = rtrim($argv[1] ?? '', '/\\');
if ($back === '' || ! is_file("$back/artisan")) {
    fwrite(STDERR, "Indique la carpeta 'back' del proyecto. Ej.: php ".basename(__FILE__)." /var/www/urme/back\n");
    exit(1);
}
require "$back/vendor/autoload.php";
$app = require "$back/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::statement('SET SESSION TRANSACTION READ ONLY');
DB::beginTransaction();

$norm = fn (?string $s) => mb_strtoupper(trim(preg_replace('/\s+/', ' ',
    iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', html_entity_decode(strip_tags((string) $s), ENT_QUOTES | ENT_HTML5)) ?: '')));
$largos = ['IONOGRAMA', 'ELECTROLITO', 'SODIO', 'POTASIO', 'CLORO', 'CLORURO', 'CALCIO', 'MAGNESIO', 'FOSFORO'];
$cortos = ['NA', 'K', 'CL', 'CA'];
$coincide = function (?string $texto) use ($norm, $largos, $cortos): array {
    $t = $norm($texto);
    $hits = array_values(array_filter($largos, fn ($w) => str_contains($t, $w)));
    foreach ($cortos as $w) {
        if (preg_match('/(^|[^A-Z0-9])'.$w.'([^A-Z0-9]|$)/', $t)) {
            $hits[] = $w;
        }
    }

    return $hits;
};
$celda = fn ($v) => str_replace('|', '/', (string) $v);

echo '# Diagnóstico de electrolitos — '.DB::connection()->getDatabaseName().' — '.date('Y-m-d H:i')."\n\n";
$tieneGrupo = DB::getSchemaBuilder()->hasColumn('productos', 'grupo_reporte');
echo '- Columna `productos.grupo_reporte`: '.($tieneGrupo ? 'existe' : 'no existe (migración aún no aplicada)')."\n";
echo '- Solicitudes totales: '.DB::table('solicitudes')->count()."\n\n";

$productos = DB::table('productos as p')
    ->join('tipo_productos as t', 't.id', '=', 'p.tipo_producto_id')
    ->where('t.es_laboratorio', true)
    ->select('p.id', 'p.nombre', 'p.deleted_at', 'p.descripcion', 't.nombre as area', 't.deleted_at as area_deleted')
    ->get()->keyBy('id');
$datos = DB::table('producto_laboratorio_datos')
    ->whereIn('producto_id', $productos->keys())
    ->select('id', 'producto_id', 'nombre', 'nombre_variable', 'unidad', 'metodo', 'muestra', 'rango_referencia', 'orden', 'deleted_at')
    ->orderBy('producto_id')->orderBy('orden')->orderBy('id')->get();

$ids = $productos->filter(fn ($p) => $coincide($p->nombre))->keys()
    ->merge($datos->filter(fn ($d) => $coincide($d->nombre) || $coincide(str_replace('_', ' ', $d->nombre_variable)))->pluck('producto_id'))
    ->unique()->sort()->values();

echo "## 1. Productos y parámetros relacionados con electrolitos\n\n";
echo "Incluye eliminados. Revise a mano los falsos positivos (p. ej. marcadores tumorales CA-125, que coinciden con \"CA\").\n\n";
echo "| producto | área | ¿activo? | parámetro (id, nombre, variable) | unidad | método | muestra | rango (texto) | orden | coincide por |\n|---|---|---|---|---|---|---|---|---|---|\n";
foreach ($ids as $pid) {
    $p = $productos[$pid];
    $activo = $p->deleted_at ? "NO (eliminado {$p->deleted_at})" : 'Sí';
    $area = $p->area.($p->area_deleted ? ' [área eliminada]' : '');
    $ds = $datos->where('producto_id', $pid);
    if ($ds->isEmpty()) {
        echo "| {$pid} {$celda($p->nombre)} | {$celda($area)} | {$activo} | (sin parámetros) | | | | | | ".implode(',', $coincide($p->nombre))." |\n";
    }
    foreach ($ds as $d) {
        $hits = array_unique(array_merge($coincide($d->nombre), $coincide(str_replace('_', ' ', $d->nombre_variable))));
        $rango = $norm($d->rango_referencia);
        echo "| {$pid} {$celda($p->nombre)} | {$celda($area)} | {$activo} | {$d->id} {$celda($d->nombre)} `{$d->nombre_variable}`"
            .($d->deleted_at ? ' (parámetro eliminado)' : '')
            .' | '.$celda($d->unidad ?? '—').' | '.$celda($d->metodo ?? '—').' | '.$celda($d->muestra ?? '—')
            .' | '.($rango !== '' ? $celda(mb_substr($rango, 0, 90)) : '**vacío**')." | {$d->orden} | ".($hits ? implode(',', $hits) : 'nombre del producto')." |\n";
    }
}

echo "\n## 2. Uso en solicitudes (solo conteos)\n\n| producto | solicitudes activas | solicitudes eliminadas | ejemplo (id / código) |\n|---|---|---|---|\n";
foreach ($ids as $pid) {
    $base = DB::table('solicitud_laboratorio_items as i')->join('solicitudes as s', 's.id', '=', 'i.solicitude_id')
        ->where('i.producto_id', $pid)->whereNull('i.deleted_at');
    $ejemplo = (clone $base)->whereNull('s.deleted_at')->orderByDesc('s.id')->select('s.id', 's.codigo_solicitud')->first();
    echo "| {$pid} {$celda($productos[$pid]->nombre)} | ".(clone $base)->whereNull('s.deleted_at')->distinct()->count('s.id')
        .' | '.(clone $base)->whereNotNull('s.deleted_at')->distinct()->count('s.id')
        .' | '.($ejemplo ? "{$ejemplo->id} / {$ejemplo->codigo_solicitud}" : '—')." |\n";
}

echo "\n## 3. Perfiles candidatos a agrupar\n\n| producto | área | parámetros activos | descripción (texto) |"
    .($tieneGrupo ? ' grupo_reporte / orden_reporte |' : '')."\n|---|---|---|---|".($tieneGrupo ? '---|' : '')."\n";
$perfiles = [865, 866, 867, 868, 869, 870, 871, 853, 854, 855, 857, 858, 860, 847, 848];
$grupos = $tieneGrupo ? DB::table('productos')->whereIn('id', $perfiles)->get(['id', 'grupo_reporte', 'orden_reporte'])->keyBy('id') : collect();
foreach ($perfiles as $pid) {
    $p = DB::table('productos as p')->leftJoin('tipo_productos as t', 't.id', '=', 'p.tipo_producto_id')
        ->where('p.id', $pid)->select('p.nombre', 'p.descripcion', 'p.deleted_at', 't.nombre as area')->first();
    if (! $p) {
        echo "| {$pid} | (no existe) | | |".($tieneGrupo ? ' |' : '')."\n";

        continue;
    }
    $n = DB::table('producto_laboratorio_datos')->where('producto_id', $pid)->whereNull('deleted_at')->count();
    $desc = $norm($p->descripcion);
    echo "| {$pid} {$celda($p->nombre)}".($p->deleted_at ? ' (eliminado)' : '')." | {$celda($p->area)} | {$n} | ".($desc !== '' ? $celda($desc) : '—').' |'
        .($tieneGrupo ? ' '.$celda($grupos[$pid]->grupo_reporte ?? '—').' / '.($grupos[$pid]->orden_reporte ?? '—').' |' : '')."\n";
}

echo "\n## 4. Unidades guardadas en MAYÚSCULAS (parámetros de laboratorio activos)\n\n| unidad | parámetros |\n|---|---|\n";
foreach ($datos->whereNull('deleted_at')->filter(fn ($d) => $d->unidad && preg_match('/[A-Z]/', $d->unidad) && $d->unidad === mb_strtoupper($d->unidad) && preg_match('/[A-Z]{2}/', $d->unidad))
    ->groupBy('unidad') as $unidad => $grupo) {
    echo "| {$celda($unidad)} | {$grupo->count()} |\n";
}

DB::rollBack();
echo "\n_Fin. Sesión de solo lectura; no se modificó nada._\n";
