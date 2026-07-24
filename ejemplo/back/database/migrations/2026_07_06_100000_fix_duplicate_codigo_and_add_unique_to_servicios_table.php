<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('servicios')
            ->select('codigo')
            ->whereNotNull('codigo')
            ->groupBy('codigo')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('codigo');

        if ($duplicates->isNotEmpty()) {
            $max = (int) DB::table('servicios')->max('codigo');

            foreach ($duplicates as $codigo) {
                $ids = DB::table('servicios')
                    ->where('codigo', $codigo)
                    ->orderBy('id')
                    ->pluck('id');

                // conserva el código en el registro más antiguo, reasigna el resto al máximo + 1
                foreach ($ids->slice(1) as $id) {
                    $max++;
                    DB::table('servicios')->where('id', $id)->update(['codigo' => $max]);
                }
            }
        }

        Schema::table('servicios', function (Blueprint $table) {
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
        });
    }
};
