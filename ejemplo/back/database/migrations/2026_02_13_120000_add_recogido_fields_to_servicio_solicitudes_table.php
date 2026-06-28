<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicio_solicitudes', function (Blueprint $table) {
            $table->boolean('fue_recogido')->default(false)->after('realizado');
            $table->string('recogido_por_personal')->nullable()->after('fue_recogido');
            $table->string('grado_parentesco')->nullable()->after('recogido_por_personal');
            $table->string('telefono_recogido')->nullable()->after('grado_parentesco');
            $table->string('ci_recogido')->nullable()->after('telefono_recogido');
            $table->dateTime('recogido_en_dia')->nullable()->after('telefono_recogido');
        });
    }

    public function down(): void
    {
        Schema::table('servicio_solicitudes', function (Blueprint $table) {
            $table->dropColumn([
                'fue_recogido',
                'recogido_por_personal',
                'grado_parentesco',
                'telefono_recogido',
                'ci_recogido',
                'recogido_en_dia',
            ]);
        });
    }
};
