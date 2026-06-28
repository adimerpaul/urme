<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compra_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->foreign('producto_id')->references('id')->on('almacen_items')->nullOnDelete();
            $table->string('nombre')->nullable();
            $table->decimal('precio', 14, 2)->nullable();
            $table->integer('cantidad')->nullable();
            $table->integer('cantidad_venta')->default(0);
            $table->decimal('total', 14, 2)->nullable();
            $table->decimal('factor', 8, 4)->nullable();
            $table->decimal('precio13', 14, 2)->nullable();
            $table->decimal('total13', 14, 2)->nullable();
            $table->decimal('precio_venta', 14, 2)->nullable();
            $table->string('estado', 30)->default('ACTIVO');
            $table->string('lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('nro_factura')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['compra_id', 'producto_id']);
            $table->index(['producto_id', 'estado']);
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_detalles');
    }
};
