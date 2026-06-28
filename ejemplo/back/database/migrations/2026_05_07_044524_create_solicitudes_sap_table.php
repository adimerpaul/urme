<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_sap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nro', 50)->nullable();
            $table->date('fecha');
            $table->string('unidad_solicitante')->nullable();
            $table->string('estado', 30)->default('PENDIENTE');
            $table->text('observaciones')->nullable();
            $table->decimal('total', 14, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['estado', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_sap');
    }
};
