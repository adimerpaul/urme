<?php

use App\Models\Paciente;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('codigo', 30)->nullable()->after('id');
        });

        Paciente::withTrashed()->each(function (Paciente $p) {
            $p->codigo = Paciente::generarCodigo($p->nombre_completo, $p->fecha_nac);
            $p->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
