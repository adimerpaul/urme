<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ProductoLaboratorioDato extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'producto_laboratorio_datos';

    protected $fillable = [
        'producto_id',
        'nombre',
        'nombre_variable',
        'unidad',
        'rango_referencia',
        'orden',
        'visible',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['visible' => 'boolean'];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function formula()
    {
        return $this->hasOne(ProductoLaboratorioFormula::class);
    }
}
