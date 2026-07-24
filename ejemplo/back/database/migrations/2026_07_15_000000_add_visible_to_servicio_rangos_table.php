<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_rangos', function (Blueprint $table) {
            $table->boolean('visible')->default(true)->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('servicio_rangos', function (Blueprint $table) {
            $table->dropColumn('visible');
        });
    }
};
