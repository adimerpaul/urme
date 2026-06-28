<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->dateTime('fecha_hora')->nullable();
            $table->string('carnet')->nullable();
            $table->string('nombre')->nullable();
            $table->string('estado', 30)->default('ACTIVO');
            $table->decimal('total', 14, 2)->nullable();
            $table->string('tipo_pago', 50)->default('EFECTIVO');
            $table->string('nro_factura')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['proveedor_id', 'fecha_hora']);
            $table->index(['estado', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
