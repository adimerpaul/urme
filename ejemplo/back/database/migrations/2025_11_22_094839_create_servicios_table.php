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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')->constrained('areas');

            $table->integer('codigo')->nullable();       // 1,2,3,...
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('metodo')->nullable();
            $table->string('subarea')->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->string('estado')->default('ACTIVO');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
