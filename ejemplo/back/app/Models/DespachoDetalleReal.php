<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class DespachoDetalleReal extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'despacho_detalle_reales';

    protected $fillable = [
        'despacho_id',
        'despacho_detalle_id',
        'almacen_item_id',
        'compra_detalle_id',
        'item',
        'unidad',
        'cantidad',
        'precio_unitario',
        'total',
        'lote',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'precio_unitario' => 'decimal:4',
        'total'          => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    protected $hidden = ['deleted_at'];

    public function despacho()
    {
        return $this->belongsTo(Despacho::class);
    }

    public function despachoDe()
    {
        return $this->belongsTo(DespachoDetalle::class, 'despacho_detalle_id');
    }

    public function almacenItem()
    {
        return $this->belongsTo(AlmacenItem::class);
    }

    public function compraDetalle()
    {
        return $this->belongsTo(CompraDetalle::class);
    }
}
