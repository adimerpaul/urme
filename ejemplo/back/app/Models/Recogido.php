<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Recogido extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'servicio_solicitudes';

    protected $fillable = [
        'solicitude_id',
        'servicio_id',
        'area_id',
        'precio',
        'nombre',
        'realizado',
        'fue_recogido',
        'recogido_por_personal',
        'grado_parentesco',
        'telefono_recogido',
        'ci_recogido',
        'recogido_en_dia',
    ];

    protected $casts = [
        'fue_recogido' => 'boolean',
        'recogido_en_dia' => 'datetime:Y-m-d H:i:s',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
