<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrega_resultados', function (Blueprint $table) {
            $table->dropForeign(['solicitude_id']);
            $table->dropUnique(['solicitude_id']);
            $table->string('area')->nullable()->after('solicitude_id');
            $table->unique(['solicitude_id', 'area']);
            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('entrega_resultados', function (Blueprint $table) {
            $table->dropForeign(['solicitude_id']);
            $table->dropUnique(['solicitude_id', 'area']);
            $table->dropColumn('area');
            $table->unique('solicitude_id');
            $table->foreign('solicitude_id')->references('id')->on('solicitudes')->onDelete('cascade');
        });
    }
};
