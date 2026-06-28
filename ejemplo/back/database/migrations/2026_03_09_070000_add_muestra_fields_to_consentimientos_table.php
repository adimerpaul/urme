<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            $table->boolean('m_orina')->nullable()->after('fecha_consentimiento');
            $table->string('hr_recoleccion_orina', 20)->nullable()->after('m_orina');
            $table->boolean('m_liquidos')->nullable()->after('hr_recoleccion_orina');
            $table->string('hr_recoleccion_liquidos', 20)->nullable()->after('m_liquidos');
            $table->boolean('m_esputo')->nullable()->after('hr_recoleccion_liquidos');
            $table->string('hr_recoleccion_esputo', 20)->nullable()->after('m_esputo');
            $table->boolean('m_secreciones')->nullable()->after('hr_recoleccion_esputo');
            $table->string('hr_recoleccion_secreciones', 20)->nullable()->after('m_secreciones');
            $table->text('observaciones')->nullable()->after('hr_recoleccion_secreciones');
        });
    }

    public function down(): void
    {
        Schema::table('consentimientos', function (Blueprint $table) {
            $table->dropColumn([
                'm_orina',
                'hr_recoleccion_orina',
                'm_liquidos',
                'hr_recoleccion_liquidos',
                'm_esputo',
                'hr_recoleccion_esputo',
                'm_secreciones',
                'hr_recoleccion_secreciones',
                'observaciones',
            ]);
        });
    }
};
