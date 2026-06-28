<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despacho_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('almacen_item_id')->nullable()->constrained('almacen_items')->nullOnDelete();
            $table->integer('item');
            $table->string('descripcion');
            $table->string('unidad', 100)->nullable();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('despacho_id');
            $table->index('almacen_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_detalles');
    }
};
