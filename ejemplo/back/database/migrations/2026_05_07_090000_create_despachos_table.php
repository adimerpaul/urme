<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('despachos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('nro', 50)->nullable();
            $table->dateTime('fecha_entrega');
            $table->string('solicitante')->nullable();
            $table->string('servicio')->nullable();
            $table->string('personal_recepcion')->nullable();
            $table->string('estado', 30)->default('DESPACHADO');
            $table->text('observaciones')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado', 'fecha_entrega']);
            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('despachos');
    }
};
