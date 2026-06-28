<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_solicitudes', function (Blueprint $table) {
            $table->string('realizado_por')->nullable()->after('realizado');
        });
    }

    public function down(): void
    {
        Schema::table('servicio_solicitudes', function (Blueprint $table) {
            $table->dropColumn('realizado_por');
        });
    }
};
