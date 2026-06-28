<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('num');
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->unsignedInteger('num');
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('subpartidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->restrictOnDelete();
            $table->unsignedInteger('num');
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subpartidas');
        Schema::dropIfExists('partidas');
        Schema::dropIfExists('grupos');
    }
};
