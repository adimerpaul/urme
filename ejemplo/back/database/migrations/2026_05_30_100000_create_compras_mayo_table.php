<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Crear tabla ────────────────────────────────────────────
        Schema::create('compras_mayo', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('n')->comment('Nro de orden del CSV');
            $table->string('nombre');
            $table->string('unidad_medida', 100)->nullable();
            $table->decimal('precio_unitario', 14, 4)->default(0);
            $table->integer('saldo_inicial')->default(0);
            $table->integer('entradas')->default(0);
            $table->integer('salidas')->default(0);
            $table->integer('saldo_final')->default(0);
            $table->unsignedBigInteger('almacen_item_id')->nullable()->index();
            $table->boolean('migrado')->default(false)->index();
            $table->text('observacion')->nullable();
            $table->timestamps();
        });

        // ── 2. Cargar CSV ─────────────────────────────────────────────
        $csvPath = public_path('inventario_ene_may_2026.csv');

        if (! file_exists($csvPath)) {
            return; // Sin CSV no falla la migración, tabla queda vacía
        }

        $handle = fopen($csvPath, 'r');
        fgetcsv($handle, 0, ';'); // saltar cabecera

        $batch = [];
        $now   = now();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 8) continue;

            [$n, $nombre, $unidad, $precio, $saldoIni, $entradas, $salidas, $saldoFin] = $row;

            $batch[] = [
                'n'               => (int) trim($n),
                'nombre'          => preg_replace('/\s+/', ' ', trim($nombre)),
                'unidad_medida'   => trim($unidad) ?: null,
                'precio_unitario' => (float) str_replace(',', '.', trim($precio)),
                'saldo_inicial'   => (int) trim($saldoIni),
                'entradas'        => (int) trim($entradas),
                'salidas'         => (int) trim($salidas),
                'saldo_final'     => (int) trim($saldoFin),
                'almacen_item_id' => null,
                'migrado'         => false,
                'observacion'     => null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            if (count($batch) >= 200) {
                DB::table('compras_mayo')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('compras_mayo')->insert($batch);
        }

        fclose($handle);

        // ── 3. Match automático con almacen_items ─────────────────────
        DB::statement("
            UPDATE compras_mayo cm
            JOIN almacen_items ai
                ON LOWER(TRIM(ai.nombre)) = LOWER(TRIM(cm.nombre))
               AND ai.deleted_at IS NULL
            SET cm.almacen_item_id = ai.id,
                cm.observacion     = 'match automático por nombre'
            WHERE cm.migrado = 0
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('compras_mayo');
    }
};
