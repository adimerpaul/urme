<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_area_tipo_muestra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('area_tipo_muestra_id')->constrained('area_tipo_muestras')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(
                ['servicio_id', 'area_tipo_muestra_id'],
                'servicio_area_tipo_muestra_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_area_tipo_muestra');
    }
};
