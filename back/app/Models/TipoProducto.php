<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TipoProducto extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'tipo_productos';

    protected $fillable = ['nombre', 'color', 'es_laboratorio', 'orden'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'es_laboratorio' => 'boolean',
    ];

    public function scopeLaboratorio($query)
    {
        return $query->where('es_laboratorio', true);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
