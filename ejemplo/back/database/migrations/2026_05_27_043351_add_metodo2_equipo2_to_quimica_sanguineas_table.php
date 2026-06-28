<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->string('metodo2')->nullable()->after('metodo');
            $table->string('equipo2')->nullable()->after('equipo');
            $table->string('metodo3')->nullable()->after('metodo2');
            $table->string('equipo3')->nullable()->after('equipo2');
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->dropColumn(['metodo2', 'equipo2', 'metodo3', 'equipo3']);
        });
    }
};
