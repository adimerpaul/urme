<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class SolitudePreAnalitica extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'solicitude_id',
        'area_tipo_muestra_id',
        'estado',
        'nombre',
        'selected',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function areaTipoMuestra()
    {
        return $this->belongsTo(AreaTipoMuestra::class, 'area_tipo_muestra_id');
    }

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }
}
