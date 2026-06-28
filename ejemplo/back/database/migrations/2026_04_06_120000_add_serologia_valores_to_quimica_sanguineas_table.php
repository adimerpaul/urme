<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->decimal('aso_valor', 8, 2)->nullable()->after('aso');
            $table->decimal('fr_valor', 8, 2)->nullable()->after('fr');
            $table->decimal('pcr_valor', 8, 2)->nullable()->after('pcr');
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->dropColumn(['aso_valor', 'fr_valor', 'pcr_valor']);
        });
    }
};
