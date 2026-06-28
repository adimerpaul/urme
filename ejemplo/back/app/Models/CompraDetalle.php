<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompraDetalle extends Model
{
    use SoftDeletes;

    protected $appends = [
        'existencia',
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'cantidad_venta' => 'float',
        'precio'         => 'decimal:4',
        'precio13'       => 'decimal:4',
        'precio_venta'   => 'decimal:4',
        'total'          => 'decimal:2',
        'total13'        => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    protected $fillable = [
        'compra_id',
        'user_id',
        'proveedor_id',
        'producto_id',
        'nombre',
        'precio',
        'cantidad',
        'cantidad_venta',
        'total',
        'factor',
        'precio13',
        'total13',
        'precio_venta',
        'estado',
        'lote',
        'fecha_vencimiento',
        'nro_factura',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function producto()
    {
        return $this->belongsTo(AlmacenItem::class, 'producto_id');
    }

    public function getExistenciaAttribute(): float
    {
        $cantidad = (float) ($this->cantidad ?? 0);
        $cantidadVenta = (float) ($this->cantidad_venta ?? 0);

        return max($cantidad - $cantidadVenta, 0);
    }
}
