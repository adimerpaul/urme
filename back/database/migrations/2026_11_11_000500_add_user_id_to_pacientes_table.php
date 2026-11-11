<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quién registró al paciente. La fecha y hora ya la guarda created_at.
 * Los pacientes existentes se completan con el usuario de su auditoría de
 * creación (si no la tiene o fue sin sesión, queda en null).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        if (Schema::hasTable('audits')) {
            DB::table('pacientes')->whereNull('user_id')->update([
                'user_id' => DB::raw("(SELECT a.user_id FROM audits a
                    WHERE a.auditable_type = 'App\\\\Models\\\\Paciente'
                      AND a.auditable_id = pacientes.id
                      AND a.event = 'created'
                      AND a.user_type = 'App\\\\Models\\\\User'
                      AND a.user_id IN (SELECT u.id FROM users u)
                    ORDER BY a.id LIMIT 1)"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
