<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->boolean('es_publico')->default(false)->after('tipo');
            $table->boolean('es_lab_urbano')->default(false)->after('es_publico');
            $table->boolean('es_lab_rural')->default(false)->after('es_lab_urbano');
            $table->boolean('es_privado')->default(false)->after('es_lab_rural');
        });

        DB::statement("UPDATE establecimientos SET es_publico    = 1 WHERE tipo = 'PUBLICO'");
        DB::statement("UPDATE establecimientos SET es_lab_urbano = 1 WHERE tipo = 'URBANO'");
        DB::statement("UPDATE establecimientos SET es_lab_rural  = 1 WHERE tipo = 'RURAL'");
        DB::statement("UPDATE establecimientos SET es_privado    = 1 WHERE tipo = 'PRIVADO'");

        Schema::table('establecimientos', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('establecimientos', function (Blueprint $table) {
            $table->enum('tipo', ['PUBLICO', 'PRIVADO', 'URBANO', 'RURAL'])->nullable()->after('nombre');
            $table->dropColumn(['es_publico', 'es_lab_urbano', 'es_lab_rural', 'es_privado']);
        });
    }
};
