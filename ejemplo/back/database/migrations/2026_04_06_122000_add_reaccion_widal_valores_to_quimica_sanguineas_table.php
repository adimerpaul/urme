<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->string('reaccion_widal_o_valor', 255)->nullable()->after('reaccion_widal_o');
            $table->string('reaccion_widal_h_valor', 255)->nullable()->after('reaccion_widal_h');
            $table->string('reaccion_widal_a_valor', 255)->nullable()->after('reaccion_widal_a');
            $table->string('reaccion_widal_b_valor', 255)->nullable()->after('reaccion_widal_b');
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->dropColumn([
                'reaccion_widal_o_valor',
                'reaccion_widal_h_valor',
                'reaccion_widal_a_valor',
                'reaccion_widal_b_valor',
            ]);
        });
    }
};
