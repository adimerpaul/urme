<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->string('aso', 50)->nullable()->change();
            $table->string('fr', 50)->nullable()->change();
            $table->string('pcr', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->decimal('aso', 8, 2)->nullable()->change();
            $table->decimal('fr', 8, 2)->nullable()->change();
            $table->decimal('pcr', 8, 2)->nullable()->change();
        });
    }
};
