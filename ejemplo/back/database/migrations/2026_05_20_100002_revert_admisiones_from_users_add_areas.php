<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quitar columnas que se agregaron por error en users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['admisiones', 'personal_turnos']);
        });

        // Agregar las dos áreas nuevas
        $now = now();
        DB::table('areas')->insert([
            [
                'name'        => 'ADMISIONES',
                'descripcion' => null,
                'title'       => 'ADMISIONES',
                'estado'      => 'ACTIVO',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'PERSONAL TURNOS',
                'descripcion' => null,
                'title'       => 'PERSONAL TURNOS',
                'estado'      => 'ACTIVO',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admisiones')->nullable();
            $table->string('personal_turnos')->nullable();
        });

        DB::table('areas')->whereIn('name', ['ADMISIONES', 'PERSONAL TURNOS'])->delete();
    }
};
