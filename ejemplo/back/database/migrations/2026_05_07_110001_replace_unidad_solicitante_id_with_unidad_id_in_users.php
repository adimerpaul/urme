<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'unidad_solicitante_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['unidad_solicitante_id']);
                });
            } catch (Throwable $e) {
                // La columna puede existir en servidores actualizados manualmente sin FK.
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('unidad_solicitante_id');
            });
        }

        if (! Schema::hasColumn('users', 'unidad_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('unidad_id')
                    ->nullable()
                    ->after('establecimiento_id')
                    ->constrained('unidades')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'unidad_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropForeign(['unidad_id']);
                });
            } catch (Throwable $e) {
                // La columna puede existir sin FK si la base fue intervenida manualmente.
            }

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('unidad_id');
            });
        }

        if (! Schema::hasColumn('users', 'unidad_solicitante_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('unidad_solicitante_id')
                    ->nullable()
                    ->after('establecimiento_id')
                    ->constrained('unidad_solicitantes')
                    ->nullOnDelete();
            });
        }
    }
};
