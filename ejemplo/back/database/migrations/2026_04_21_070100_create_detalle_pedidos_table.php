<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->foreign('producto_id')->references('id')->on('almacen_items')->nullOnDelete();
            $table->integer('cantidad')->nullable();
            $table->decimal('precio_unitario', 14, 2)->nullable();
            $table->decimal('subtotal', 14, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['pedido_id', 'producto_id']);
            $table->index(['producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
