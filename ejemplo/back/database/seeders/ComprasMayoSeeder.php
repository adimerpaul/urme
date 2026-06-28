<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprasMayoSeeder extends Seeder
{
    /**
     * Carga el CSV "INVENTARIO ENE A MAY-2026.csv" en la tabla compras_mayo.
     *
     * Coloca el CSV en:  storage/app/inventario_ene_may_2026.csv
     * Ejecutar con:      php artisan db:seed --class=ComprasMayoSeeder
     */
    public function run(): void
    {
        $csvPath = public_path('inventario_ene_may_2026.csv');

        if (! file_exists($csvPath)) {
            $this->command->error("CSV no encontrado en: {$csvPath}");
            $this->command->info("Copia el archivo a public/inventario_ene_may_2026.csv y vuelve a ejecutar.");
            return;
        }

        // Limpiar tabla antes de cargar para que sea idempotente
        DB::table('compras_mayo')->truncate();

        $handle = fopen($csvPath, 'r');

        // Saltar cabecera
        fgetcsv($handle, 0, ';');

        $batch = [];
        $total = 0;
        $now   = now();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 8) continue;

            [$n, $nombre, $unidad, $precio, $saldoIni, $entradas, $salidas, $saldoFin] = $row;

            // Normalizar decimal: coma → punto
            $precio = (float) str_replace(',', '.', trim($precio));

            $batch[] = [
                'n'               => (int) trim($n),
                'nombre'          => trim($nombre),
                'unidad_medida'   => trim($unidad) ?: null,
                'precio_unitario' => $precio,
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

            // Insertar en chunks de 200
            if (count($batch) >= 200) {
                DB::table('compras_mayo')->insert($batch);
                $total += count($batch);
                $batch  = [];
                $this->command->info("  Insertadas {$total} filas...");
            }
        }

        // Insertar el último batch
        if ($batch) {
            DB::table('compras_mayo')->insert($batch);
            $total += count($batch);
        }

        fclose($handle);

        $this->command->info("Carga completada: {$total} filas en compras_mayo.");

        // ── Intentar hacer match automático con almacen_items ──────────
        $this->command->info("Buscando coincidencias con almacen_items...");

        $matched = DB::statement("
            UPDATE compras_mayo cm
            JOIN almacen_items ai
                ON LOWER(TRIM(ai.nombre)) = LOWER(TRIM(cm.nombre))
               AND ai.deleted_at IS NULL
            SET cm.almacen_item_id = ai.id,
                cm.observacion     = 'match automático por nombre'
            WHERE cm.migrado = 0
        ");

        $sinMatch = DB::table('compras_mayo')
            ->whereNull('almacen_item_id')
            ->count();

        $conMatch = DB::table('compras_mayo')
            ->whereNotNull('almacen_item_id')
            ->count();

        $this->command->info("  Con match:  {$conMatch}");
        $this->command->warn("  Sin match:  {$sinMatch} (revisar manualmente)");
        $this->command->info("Listo. Ejecuta el siguiente paso de migración cuando estés listo.");
    }
}
