<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now    = now();
        $userId = DB::table('users')->where('role', 'Administrador')->value('id') ?? 1;

        // ── 1. Actualizar almacen_items desde compras_mayo ────────────
        DB::statement("
            UPDATE almacen_items ai
            JOIN compras_mayo cm ON cm.almacen_item_id = ai.id
            SET
                ai.unidad_medida    = CASE WHEN cm.unidad_medida IS NOT NULL AND cm.unidad_medida != ''
                                          THEN cm.unidad_medida ELSE ai.unidad_medida END,
                ai.precio_unitario  = CASE WHEN cm.precio_unitario > 0
                                          THEN cm.precio_unitario ELSE ai.precio_unitario END,
                ai.updated_at       = NOW()
            WHERE ai.deleted_at IS NULL
        ");

        // ── 2. Crear cabecera de compra general ───────────────────────
        $compraId = DB::table('compras')->insertGetId([
            'user_id'          => $userId,
            'proveedor_id'     => null,
            'fecha_hora'       => $now,
            'tipo_registro'    => 'ENTRADA',
            'motivo_registro'  => 'INVENTARIO INICIAL',
            'comentario'       => 'Carga masiva de inventario ENE-MAY 2026',
            'estado'           => 'ACTIVO',
            'total'            => 0,
            'tipo_pago'        => 'EFECTIVO',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        // ── 3. Insertar compra_detalles por cada producto con match ───
        //   cantidad       = saldo_inicial + entradas  (total que ingresó)
        //   cantidad_venta = salidas                   (lo que salió)
        //   existencia     = cantidad - cantidad_venta = saldo_final  ✓

        $items = DB::table('compras_mayo')
            ->whereNotNull('almacen_item_id')
            ->where('saldo_final', '>', 0)
            ->select(
                'almacen_item_id',
                'nombre',
                'precio_unitario',
                'saldo_final',
                'id as mayo_id'
            )
            ->get();

        $totalCompra = 0;
        $batch       = [];
        $mayoIds     = [];

        foreach ($items as $item) {
            $cantidad    = (int) $item->saldo_final;
            $precio      = (float) $item->precio_unitario;
            $total       = round($precio * $cantidad, 2);
            $totalCompra += $total;

            $batch[] = [
                'compra_id'      => $compraId,
                'user_id'        => $userId,
                'proveedor_id'   => null,
                'producto_id'    => $item->almacen_item_id,
                'nombre'         => $item->nombre,
                'precio'         => $precio,
                'cantidad'       => $cantidad,
                'cantidad_venta' => 0,
                'total'          => $total,
                'estado'         => 'ACTIVO',
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $mayoIds[] = $item->mayo_id;

            if (count($batch) >= 200) {
                DB::table('compra_detalles')->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table('compra_detalles')->insert($batch);
        }

        // ── 4. Actualizar total de la compra ──────────────────────────
        DB::table('compras')
            ->where('id', $compraId)
            ->update(['total' => round($totalCompra, 2)]);

        // ── 5. Marcar registros procesados en compras_mayo ────────────
        if ($mayoIds) {
            DB::table('compras_mayo')
                ->whereIn('id', $mayoIds)
                ->update([
                    'migrado'    => true,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Revertir: eliminar la compra general y sus detalles
        $compra = DB::table('compras')
            ->where('motivo_registro', 'INVENTARIO INICIAL')
            ->where('comentario', 'Carga masiva de inventario ENE-MAY 2026')
            ->first();

        if ($compra) {
            DB::table('compra_detalles')->where('compra_id', $compra->id)->delete();
            DB::table('compras')->where('id', $compra->id)->delete();
        }

        DB::table('compras_mayo')->update(['migrado' => false]);
    }
};
