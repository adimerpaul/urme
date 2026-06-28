<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
//        Schema::table('consentimientos', function (Blueprint $table) {
//            $table->boolean('m_heces')->nullable()->after('m_secreciones');
//            $table->string('hr_recoleccion_heces', 20)->nullable()->after('m_heces');
//        });
    }

    public function down(): void
    {
//        Schema::table('consentimientos', function (Blueprint $table) {
//            $table->dropColumn([
//                'm_heces',
//                'hr_recoleccion_heces',
//            ]);
//        });
    }
};
