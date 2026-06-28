<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('despacho_detalles')) {
            return;
        }

        Schema::table('despacho_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('despacho_detalles', 'compra_detalle_id')) {
                $table->foreignId('compra_detalle_id')
                    ->nullable()
                    ->after('almacen_item_id')
                    ->constrained('compra_detalles')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('despacho_detalles', 'lote')) {
                $table->string('lote')->nullable()->after('total');
            }

            if (! Schema::hasColumn('despacho_detalles', 'fecha_vencimiento')) {
                $table->date('fecha_vencimiento')->nullable()->after('lote');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('despacho_detalles')) {
            return;
        }

        Schema::table('despacho_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('despacho_detalles', 'compra_detalle_id')) {
                $table->dropConstrainedForeignId('compra_detalle_id');
            }

            if (Schema::hasColumn('despacho_detalles', 'fecha_vencimiento')) {
                $table->dropColumn('fecha_vencimiento');
            }

            if (Schema::hasColumn('despacho_detalles', 'lote')) {
                $table->dropColumn('lote');
            }
        });
    }
};
