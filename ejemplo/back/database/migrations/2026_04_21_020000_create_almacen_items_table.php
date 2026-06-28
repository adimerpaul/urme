<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('almacen_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subpartida_id')->constrained('subpartidas')->restrictOnDelete();
            $table->string('nombre');
            $table->string('unidad_medida', 100)->nullable();
            $table->decimal('precio_unitario', 14, 4)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subpartida_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almacen_items');
    }
};
