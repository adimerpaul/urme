<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'hematologias',
        'quimica_sanguineas',
        'uroanalisis',
        'parasitologias',
        'panel_respiratorios',
        'panel_sexuales',
        'papiloma_humanos',
        'cultivo_antibiogramas',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('user_presentacion_id')->nullable()->after('id');
                $t->timestamp('fecha_presentacion')->nullable()->after('user_presentacion_id');
                $t->foreign('user_presentacion_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['user_presentacion_id']);
                $t->dropColumn(['user_presentacion_id', 'fecha_presentacion']);
            });
        }
    }
};
