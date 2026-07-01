<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Producto extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'productos';

    protected $fillable = [
        'codigo', 'nombre', 'descripcion', 'marca',
        'fabricante_id', 'unidad_id',
        'tipo_producto_id', 'precio',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class);
    }

    public function inventarios()
    {
        return $this->hasMany(ProductoInventario::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }
}
