<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class TipoProducto extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'tipo_productos';

    protected $fillable = ['nombre', 'color'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
