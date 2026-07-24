<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('area_rangos', function (Blueprint $table) {
            foreach (range(6, 8) as $n) {
                $table->string("rango_{$n}_descripcion")->nullable();
                $table->double("rango_{$n}_minimo")->nullable();
                $table->double("rango_{$n}_maximo")->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('area_rangos', function (Blueprint $table) {
            foreach (range(6, 8) as $n) {
                $table->dropColumn(["rango_{$n}_descripcion", "rango_{$n}_minimo", "rango_{$n}_maximo"]);
            }
        });
    }
};
