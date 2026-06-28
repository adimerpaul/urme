<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            if (! Schema::hasColumn('quimica_sanguineas', 'rpr_dilucion')) {
                $table->string('rpr_dilucion', 50)->nullable()->after('rpr');
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'test_embarazo_fum')) {
                $table->date('test_embarazo_fum')->nullable()->after('test_embarazo');
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'aso_dilucion')) {
                $table->string('aso_dilucion', 50)->nullable();
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'fr_dilucion')) {
                $table->string('fr_dilucion', 50)->nullable();
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'pcr_dilucion')) {
                $table->string('pcr_dilucion', 50)->nullable();
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'microalbuminuria')) {
                $table->decimal('microalbuminuria', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'gasometria_observacion')) {
                $table->text('gasometria_observacion')->nullable();
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'gasometria_muestra_estado')) {
                $table->string('gasometria_muestra_estado', 50)->nullable();
            }
            if (! Schema::hasColumn('quimica_sanguineas', 'citoquimico_observaciones')) {
                $table->text('citoquimico_observaciones')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $cols = [
                'rpr_dilucion', 'test_embarazo_fum',
                'aso_dilucion', 'fr_dilucion', 'pcr_dilucion',
                'microalbuminuria',
                'gasometria_observacion', 'gasometria_muestra_estado',
                'citoquimico_observaciones',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('quimica_sanguineas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
