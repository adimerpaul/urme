<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            $table->foreignId('solicitude_id')
                ->nullable()
                ->after('paciente_id')
                ->constrained('solicitudes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solicitude_id');
        });
    }
};
