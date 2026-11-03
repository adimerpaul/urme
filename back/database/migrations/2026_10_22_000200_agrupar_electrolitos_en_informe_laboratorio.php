<?php

use Illuminate\Database\Migrations\Migration;

/**
 * NEUTRALIZADA A PROPÓSITO (2026-11-02). up() y down() no tocan la base.
 *
 * Esta migración completaba grupo_reporte = 'Electrolitos' y orden_reporte 1/2/3
 * en los productos de laboratorio SODIO, POTASIO y CLORO que no tuvieran grupo.
 * Se dejó sin efecto porque en producción ninguna migración debe modificar datos
 * existentes: solo se admiten migraciones de esquema aditivas. Los grupos del
 * informe (grupo_reporte / orden_reporte) los asigna el bioquímico desde la
 * pantalla de laboratorio.
 *
 * Se conserva el archivo (y no se borra ni se mueve) para que quede registrada
 * como ejecutada donde aún esté pendiente, sin cambiar nada. La versión original
 * está en el historial de git de este archivo (commit 09d43a6).
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
