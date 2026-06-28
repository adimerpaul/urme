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
        Schema::create('solicitude_formularios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitude_id');
            $table->unsignedBigInteger('formulario_id');
            $table->unsignedBigInteger('area_id')->nullable();

            $table->foreign('area_id')->references('id')->on('areas');
            $table->foreign('solicitude_id')->references('id')->on('solicitudes');
            $table->foreign('formulario_id')->references('id')->on('formularios');

            $table->string('nombre')->nullable();
            $table->text('html')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitude_formularios');
    }
};
