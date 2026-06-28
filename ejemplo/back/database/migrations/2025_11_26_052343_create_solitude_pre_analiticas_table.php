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
        Schema::create('solitude_pre_analiticas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_tipo_muestra_id');
            $table->unsignedBigInteger('solicitude_id');

            $table->string('estado')->default('pendiente'); // nuevo
            $table->string('nombre')->nullable();
            $table->boolean('selected')->nullable();

            $table->foreign('area_tipo_muestra_id')
                ->references('id')->on('area_tipo_muestras')
                ->onDelete('cascade');

            $table->foreign('solicitude_id')
                ->references('id')->on('solicitudes')
                ->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solitude_pre_analiticas');
    }
};
