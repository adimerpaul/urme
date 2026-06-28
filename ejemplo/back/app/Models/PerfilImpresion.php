<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class PerfilImpresion extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'perfiles_impresion';

    protected $fillable = ['nombre', 'codigo'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function items()
    {
        return $this->hasMany(PerfilImpresionItem::class, 'perfil_id');
    }
}
