<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Servicio extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'area_id',
        'codigo',
        'nombre',
        'metodo',
        'precio',
        'estado',
        'subarea',
        'descripcion'
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    public function establecimientos()
    {
        return $this->belongsToMany(
            Establecimiento::class,
            'establecimiento_servicios',
            'servicio_id',
            'establecimiento_id'
        )->withTimestamps();
    }
    public function solicitudes()
    {
        return $this->belongsToMany(
            \App\Models\Solicitude::class,
            'servicio_solicitudes',
            'servicio_id',
            'solicitude_id'
        )->withPivot('precio')->withTimestamps();
    }

    public function tiposMuestra()
    {
        return $this->belongsToMany(
            AreaTipoMuestra::class,
            'servicio_area_tipo_muestra',
            'servicio_id',
            'area_tipo_muestra_id'
        )->withTimestamps();
    }

    public function formulas()
    {
        return $this->hasMany(ServicioFormula::class)->orderBy('orden');
    }

    public function rangos()
    {
        return $this->belongsToMany(
            AreaRango::class,
            'servicio_rangos',
            'servicio_id',
            'area_rango_id'
        )->withPivot('nombre_variable', 'orden')->withTimestamps()->orderByPivot('orden');
    }
}
