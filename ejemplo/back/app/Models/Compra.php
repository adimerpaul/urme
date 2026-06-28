<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\DespachoDetalle;
use App\Models\DespachoDetalleReal;

class Compra extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'numero',
        'proveedor_id',
        'unidad_id',
        'fecha_hora',
        'tipo_registro',
        'motivo_registro',
        'carnet',
        'nombre',
        'comentario',
        'estado',
        'total',
        'retencion_porcentaje',
        'tipo_pago',
        'nro_factura',
        'categoria_programatica',
        'orden_de_compra',
        'codigo_interno',
        'hoja_de_ruta',
    ];

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function despachoDetalles()
    {
        return $this->hasManyThrough(DespachoDetalle::class, CompraDetalle::class, 'compra_id', 'compra_detalle_id');
    }

    public function despachoDetalleReales()
    {
        return $this->hasManyThrough(DespachoDetalleReal::class, CompraDetalle::class, 'compra_id', 'compra_detalle_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
