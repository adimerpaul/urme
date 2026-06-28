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
        Schema::create('area_rangos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id');
            $table->string('rango_nombre');
            $table->double('rango_minimo')->nullable();
            $table->double('rango_maximo')->nullable();
            $table->string('unidad')->nullable();
            $table->string('interpretacion')->nullable();
            $table->string('lista')->nullable();
            $table->string('textarea')->nullable();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_rangos');
    }
};
