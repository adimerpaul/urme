<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('area_rangos', function (Blueprint $table) {
            $table->string('analito')->nullable()->after('area_id');
            $table->string('metodo', 50)->nullable()->after('analito');
            $table->string('resultado', 100)->nullable()->after('metodo');
            $table->string('muestra', 100)->nullable()->after('interpretacion');
            $table->string('marca', 100)->nullable()->after('muestra');
            $table->string('perfil', 150)->nullable()->after('marca');
        });

        $now = now();

        // HORMONAS CLIA — MINDRAY CL 1200i — PERFIL TIROIDEO
        DB::table('area_rangos')->insert([
            [
                'area_id'       => 6,
                'analito'       => 'TIROTROPINA (TSH)',
                'rango_nombre'  => 'TIROTROPINA (TSH)',
                'metodo'        => 'CLIA',
                'resultado'     => null,
                'unidad'        => 'uIU/ml',
                'rango_minimo'  => 0.35,
                'rango_maximo'  => 5.1,
                'interpretacion'=> '0,35 a 5,1 uIU/ml',
                'muestra'       => 'SUERO',
                'marca'         => 'MINDRAY',
                'perfil'        => 'PERFIL TIROIDEO',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'area_id'       => 6,
                'analito'       => 'TRIYODOTIRONINA (T3)',
                'rango_nombre'  => 'TRIYODOTIRONINA (T3)',
                'metodo'        => 'CLIA',
                'resultado'     => null,
                'unidad'        => 'ng/ml',
                'rango_minimo'  => 0.58,
                'rango_maximo'  => 1.62,
                'interpretacion'=> '0,58 a 1,62 ng/ml',
                'muestra'       => 'SUERO',
                'marca'         => 'MINDRAY',
                'perfil'        => 'PERFIL TIROIDEO',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'area_id'       => 6,
                'analito'       => 'TIROXINA LIBRE (FT4)',
                'rango_nombre'  => 'TIROXINA LIBRE (FT4)',
                'metodo'        => 'CLIA',
                'resultado'     => null,
                'unidad'        => 'ng/dl',
                'rango_minimo'  => 0.87,
                'rango_maximo'  => 1.85,
                'interpretacion'=> '0,87 a 1,85 ng/dl',
                'muestra'       => 'SUERO',
                'marca'         => 'MINDRAY',
                'perfil'        => 'PERFIL TIROIDEO',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'area_id'       => 6,
                'analito'       => 'TIROXINA TOTAL (T4)',
                'rango_nombre'  => 'TIROXINA TOTAL (T4)',
                'metodo'        => 'CLIA',
                'resultado'     => null,
                'unidad'        => 'ug/dl',
                'rango_minimo'  => 5.0,
                'rango_maximo'  => 14.5,
                'interpretacion'=> '5,0 a 14,5 ug/dl',
                'muestra'       => 'SUERO',
                'marca'         => 'MINDRAY',
                'perfil'        => 'PERFIL TIROIDEO',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'area_id'       => 6,
                'analito'       => 'TIROGLOBULINA (Tg)',
                'rango_nombre'  => 'TIROGLOBULINA (Tg)',
                'metodo'        => 'CLIA',
                'resultado'     => null,
                'unidad'        => 'ng/ml',
                'rango_minimo'  => 1.28,
                'rango_maximo'  => 50,
                'interpretacion'=> '1,28 a 50 ng/ml',
                'muestra'       => 'SUERO',
                'marca'         => 'MINDRAY',
                'perfil'        => 'PERFIL TIROIDEO',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('area_rangos')->where('area_id', 6)->delete();

        Schema::table('area_rangos', function (Blueprint $table) {
            $table->dropColumn(['analito', 'metodo', 'resultado', 'muestra', 'marca', 'perfil']);
        });
    }
};
