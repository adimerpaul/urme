<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class AlmacenItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'subpartida_id',
        'nombre',
        'unidad_medida',
        'precio_unitario',
        'imagen',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:4',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function subpartida()
    {
        return $this->belongsTo(Subpartida::class);
    }

    /**
     * Devuelve el stock real calculado (compras - despachos) para un conjunto de IDs.
     * Retorna un array [producto_id => cantidad].
     */
    public static function stockByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $despachos = "COALESCE((
            SELECT SUM(dd.cantidad)
            FROM despacho_detalles dd
            INNER JOIN despachos d ON d.id = dd.despacho_id
            WHERE dd.almacen_item_id = almacen_items.id
              AND dd.compra_detalle_id IS NULL
              AND dd.deleted_at IS NULL
              AND d.estado <> 'ANULADO'
              AND d.deleted_at IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM despacho_detalle_reales ddr
                  WHERE ddr.despacho_detalle_id = dd.id AND ddr.deleted_at IS NULL
              )
        ), 0)";

        $cantidad = "COALESCE(SUM(CASE
            WHEN compras.id IS NULL THEN 0
            WHEN compras.tipo_registro = 'SALIDA' THEN -COALESCE(compra_detalles.cantidad, 0)
            ELSE COALESCE(compra_detalles.cantidad, 0) - COALESCE(compra_detalles.cantidad_venta, 0)
        END), 0) - {$despachos}";

        return DB::table('almacen_items')
            ->leftJoin('compra_detalles', function ($join) {
                $join->on('compra_detalles.producto_id', '=', 'almacen_items.id')
                    ->whereNull('compra_detalles.deleted_at')
                    ->whereRaw("UPPER(COALESCE(compra_detalles.estado, '')) = 'ACTIVO'");
            })
            ->leftJoin('compras', function ($join) {
                $join->on('compras.id', '=', 'compra_detalles.compra_id')
                    ->whereNull('compras.deleted_at')
                    ->where('compras.estado', '=', 'ACTIVO');
            })
            ->whereIn('almacen_items.id', $ids)
            ->whereNull('almacen_items.deleted_at')
            ->select('almacen_items.id')
            ->selectRaw("{$cantidad} as cantidad")
            ->groupBy('almacen_items.id')
            ->get()
            ->pluck('cantidad', 'id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }
}
