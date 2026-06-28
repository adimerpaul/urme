<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha_hora')->nullable();
            $table->string('nombre_usuario', 255)->nullable();
            $table->string('estado', 30)->default('PENDIENTE');
            $table->decimal('total', 14, 2)->nullable();
            $table->boolean('modificado')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'fecha_hora']);
            $table->index(['estado', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
