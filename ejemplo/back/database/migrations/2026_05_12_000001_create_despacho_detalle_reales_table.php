<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despacho_detalle_reales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('despacho_id')->constrained('despachos')->cascadeOnDelete();
            $table->foreignId('despacho_detalle_id')->constrained('despacho_detalles')->cascadeOnDelete();
            $table->foreignId('almacen_item_id')->nullable()->constrained('almacen_items')->nullOnDelete();
            $table->foreignId('compra_detalle_id')->nullable()->constrained('compra_detalles')->nullOnDelete();
            $table->integer('item');
            $table->string('unidad', 100)->nullable();
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 14, 4)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('despacho_id');
            $table->index('despacho_detalle_id');
            $table->index('compra_detalle_id');
            $table->index('almacen_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despacho_detalle_reales');
    }
};
