<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UnidadSolicitante extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'nombre',
        'abreviatura',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function solicitudes()
    {
        return $this->hasMany(Solicitude::class, 'unidad_solicitante_id');
    }
}
