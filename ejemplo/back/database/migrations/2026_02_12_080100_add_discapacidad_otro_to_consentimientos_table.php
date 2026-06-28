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
            $table->string('discapacidad_otro')->nullable()->after('discapacidad_cual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            $table->dropColumn('discapacidad_otro');
        });
    }
};
