<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_solicitudes', function (Blueprint $table) {
            $table->unsignedBigInteger('user_presentacion_id')->nullable()->after('ci_recogido');
            $table->timestamp('fecha_presentacion')->nullable()->after('user_presentacion_id');
            $table->foreign('user_presentacion_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('servicio_solicitudes', function (Blueprint $table) {
            $table->dropForeign(['user_presentacion_id']);
            $table->dropColumn(['user_presentacion_id', 'fecha_presentacion']);
        });
    }
};
