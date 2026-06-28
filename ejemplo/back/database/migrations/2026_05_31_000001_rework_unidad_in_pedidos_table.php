<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('unidad');
            $table->foreignId('unidad_id')->nullable()->after('nombre_usuario')
                ->constrained('unidades')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign(['unidad_id']);
            $table->dropColumn('unidad_id');
            $table->string('unidad', 255)->nullable()->after('nombre_usuario');
        });
    }
};
