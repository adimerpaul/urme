<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class VentaDetalle extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id', 'producto_id', 'compra_detalle_id', 'nombre', 'lote',
        'fecha_vencimiento', 'precio', 'cantidad', 'total',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'precio' => 'decimal:2',
        'cantidad' => 'decimal:4',
        'total' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function compraDetalle()
    {
        return $this->belongsTo(CompraDetalle::class);
    }
}
