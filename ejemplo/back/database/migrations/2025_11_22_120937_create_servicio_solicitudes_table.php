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
        Schema::create('servicio_solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitude_id');
            $table->unsignedBigInteger('servicio_id');
            $table->unsignedBigInteger('area_id');
            $table->decimal('precio', 10, 2)->default(0);
            $table->string('nombre')->nullable();
            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');
            $table->foreign('servicio_id')->references('id')->on('servicios')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->string('realizado')->default('PENDIENTE');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicio_solicitudes');
    }
};
