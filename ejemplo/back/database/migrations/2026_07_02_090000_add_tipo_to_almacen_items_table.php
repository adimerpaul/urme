<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('almacen_items', function (Blueprint $table) {
            $table->dropForeign(['subpartida_id']);
        });

        DB::statement('ALTER TABLE almacen_items MODIFY subpartida_id BIGINT UNSIGNED NULL');

        Schema::table('almacen_items', function (Blueprint $table) {
            $table->foreign('subpartida_id')->references('id')->on('subpartidas')->restrictOnDelete();
            $table->string('tipo')->nullable()->after('subpartida_id');
            $table->string('codigo', 50)->nullable()->after('tipo');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('almacen_items', function (Blueprint $table) {
            $table->dropForeign(['subpartida_id']);
            $table->dropIndex(['tipo']);
            $table->dropColumn(['tipo', 'codigo']);
        });

        DB::statement('ALTER TABLE almacen_items MODIFY subpartida_id BIGINT UNSIGNED NOT NULL');

        Schema::table('almacen_items', function (Blueprint $table) {
            $table->foreign('subpartida_id')->references('id')->on('subpartidas')->restrictOnDelete();
        });
    }
};
