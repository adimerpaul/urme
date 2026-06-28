<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consentimientos', function ($table) {
            $table->renameColumn('correccion', 'hr_recoleccion');
        });
    }

    public function down(): void
    {
        Schema::table('consentimientos', function ($table) {
            $table->renameColumn('hr_recoleccion', 'correccion');
        });
    }
};
