<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('carnet')->nullable();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->string('email')->nullable();
            $table->string('nit')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('contacto')->nullable();
            $table->string('estado', 30)->nullable()->default('Activo');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('proveedores')->insert([
            'nombre' => 'GENERAL',
            'estado' => 'Activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
