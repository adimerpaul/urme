<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_rangos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->onDelete('cascade');
            $table->foreignId('area_rango_id')->constrained('area_rangos')->onDelete('cascade');
            $table->string('nombre_variable')->nullable();
            $table->timestamps();
            $table->unique(['servicio_id', 'area_rango_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_rangos');
    }
};
