<?php

use Illuminate\Database\Migrations\Migration;

/**
 * NEUTRALIZADA A PROPÓSITO (2026-11-03). up() y down() no tocan la base.
 *
 * Esta migración cargaba el inventario inicial de farmacia: BORRABA todas las
 * compras y sus detalles, dejaba ventas y bajas sin vínculo al lote, y creaba
 * una compra "INVENTARIO INICIAL" con el conteo físico, además de los productos,
 * fabricantes y unidades que faltaran. Se dejó sin efecto porque en producción
 * ninguna migración debe modificar datos existentes: el sistema ya está
 * configurado y funcionando, y ejecutarla otra vez borraría el inventario y su
 * historial. Solo se admiten migraciones de esquema aditivas.
 *
 * Se conserva el archivo (y no se borra ni se mueve) para que quede registrada
 * como ejecutada donde aún esté pendiente, sin cambiar nada. La versión original
 * (con el conteo completo) está en el historial de git de este archivo
 * (commit 143a350).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sin efecto a propósito: ver el comentario de la clase.
    }

    public function down(): void
    {
        // Sin efecto a propósito: ver el comentario de la clase.
    }
};
