<?php

use Illuminate\Database\Migrations\Migration;

/**
 * NEUTRALIZADA A PROPÓSITO (2026-11-03). up() y down() no tocan la base.
 *
 * Esta migración reiniciaba el inventario de farmacia con el conteo oficial del
 * 28-09-2026: BORRABA las compras, bajas, ventas solo de farmacia y cierres de
 * caja anteriores a esa fecha, daba de baja los productos de farmacia y los
 * creaba de nuevo desde el conteo, con una compra "INVENTARIO OFICIAL DE
 * FARMACIA". Se dejó sin efecto porque en producción ninguna migración debe
 * modificar datos existentes: el sistema ya está configurado y funcionando, y
 * ejecutarla otra vez borraría inventario, ventas y cierres de caja. Solo se
 * admiten migraciones de esquema aditivas.
 *
 * Se conserva el archivo (y no se borra ni se mueve) para que quede registrada
 * como ejecutada donde aún esté pendiente, sin cambiar nada. La versión original
 * (con el conteo completo) está en el historial de git de este archivo
 * (commit 87bcc39).
 *
 * Nota: 2026_10_06_000100_actualizar_inventario_farmacia_con_precios espera los
 * productos que creaba esta migración; en una instalación desde cero (base
 * vacía) fallará al no encontrarlos.
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
