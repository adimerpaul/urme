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
        Schema::create('entrega_resultados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitude_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->date('fecha_entrega');
            $table->time('hora_entrega');
            $table->string('observaciones')->nullable();
            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrega_resultados');
    }
};
