<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudSapDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'solicitud_sap_detalles';

    protected $fillable = [
        'solicitud_sap_id',
        'almacen_item_id',
        'imagen',
        'item',
        'part_presup',
        'descripcion',
        'unidad',
        'cantidad',
        'precio_unitario',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'precio_unitario' => 'float',
        'total' => 'float',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function solicitudSap()
    {
        return $this->belongsTo(SolicitudSap::class);
    }
}
