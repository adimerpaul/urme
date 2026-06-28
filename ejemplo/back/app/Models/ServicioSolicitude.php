<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class ServicioSolicitude extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

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
        'recogido_en_dia',
        'ci_recogido',
    ];

    protected $casts = [
        'fue_recogido' => 'boolean',
        'recogido_en_dia' => 'datetime:Y-m-d H:i:s',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];
    function servicio(){
        return $this->belongsTo(Servicio::class);
    }
    function solicitud(){
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }
    function area(){
        return $this->belongsTo(Area::class);
    }
}
