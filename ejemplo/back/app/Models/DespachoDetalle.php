<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DespachoDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'despacho_detalles';

    protected $fillable = [
        'despacho_id',
        'almacen_item_id',
        'compra_detalle_id',
        'item',
        'descripcion',
        'unidad',
        'cantidad',
        'precio_unitario',
        'total',
        'lote',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'precio_unitario' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    protected $hidden = ['deleted_at'];

    public function despacho()
    {
        return $this->belongsTo(Despacho::class);
    }

    public function almacenItem()
    {
        return $this->belongsTo(AlmacenItem::class);
    }

    public function compraDetalle()
    {
        return $this->belongsTo(CompraDetalle::class);
    }

    public function reales()
    {
        return $this->hasMany(DespachoDetalleReal::class, 'despacho_detalle_id');
    }
}
