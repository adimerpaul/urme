<?php

use Illuminate\Database\Migrations\Migration;

/**
 * NEUTRALIZADA A PROPÓSITO (2026-11-02). up() y down() no tocan la base.
 *
 * Esta migración actualizaba el precio normal de 247 exámenes de laboratorio
 * (precios de octubre de 2026) y su down() reescribía el "precio anterior" de
 * la misma lista. Se dejó sin efecto porque los precios NO deben modificarse
 * por migración: se ejecuta en cada `php artisan migrate` de cualquier entorno
 * (local, pruebas, producción) sin una revisión explícita de los precios.
 *
 * Se conserva el archivo (y no se borra ni se mueve) para que quede registrada
 * como ejecutada donde aún esté pendiente, sin cambiar nada, y para que un
 * rollback no reescriba precios donde ya se aplicó.
 *
 * La lista original (id, nombre, precio anterior, precio nuevo) está en el
 * historial de git de este archivo (commit 2064e28). Si algún día se quiere
 * aplicar esa lista, hay que hacerlo con una migración NUEVA y revisada contra
 * los ids y nombres reales de producción; no reactivar esta.
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
