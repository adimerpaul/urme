<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            // Columnas que la migración 2026_03_10 dejó comentadas y nunca se crearon
            if (!Schema::hasColumn('consentimientos', 'm_heces')) {
                $table->boolean('m_heces')->nullable()->default(false)->after('m_secreciones');
            }
            if (!Schema::hasColumn('consentimientos', 'hr_recoleccion_heces')) {
                $table->string('hr_recoleccion_heces', 20)->nullable()->after('m_heces');
            }

            // Nuevo campo de corrección
            if (!Schema::hasColumn('consentimientos', 'correccion')) {
                $table->text('correccion')->nullable()->after('observaciones');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['m_heces', 'hr_recoleccion_heces', 'correccion'],
                fn($col) => Schema::hasColumn('consentimientos', $col)
            ));
        });
    }
};
