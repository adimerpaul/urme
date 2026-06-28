<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->string('gasometria_tipo')->nullable()->after('gasometria_observacion');
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->dropColumn('gasometria_tipo');
        });
    }
};
