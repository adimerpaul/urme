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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();

            $table->string('nombre')->nullable();
            $table->string('especialidad')->nullable();
            $table->string('ci', 50)->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('email')->nullable();
            $table->string('registro')->nullable(); // registro profesional
            $table->string('estado')->nullable();   // ACTIVO / INACTIVO
            $table->unsignedBigInteger('establecimiento_id')->nullable();
            $table->foreign('establecimiento_id')->references('id')->on('establecimientos');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
