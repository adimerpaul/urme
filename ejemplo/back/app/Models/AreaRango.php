<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class AreaRango extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;


    protected $fillable = [
        'area_id',
        'analito',
        'metodo',
        'resultado',
        'rango_nombre',
        'rango_descripcion',
        'rango_minimo',
        'rango_maximo',
        'rango_2_descripcion', 'rango_2_minimo', 'rango_2_maximo',
        'rango_3_descripcion', 'rango_3_minimo', 'rango_3_maximo',
        'rango_4_descripcion', 'rango_4_minimo', 'rango_4_maximo',
        'rango_5_descripcion', 'rango_5_minimo', 'rango_5_maximo',
        'rango_6_descripcion', 'rango_6_minimo', 'rango_6_maximo',
        'rango_7_descripcion', 'rango_7_minimo', 'rango_7_maximo',
        'rango_8_descripcion', 'rango_8_minimo', 'rango_8_maximo',
        'unidad',
        'interpretacion',
        'muestra',
        'marca',
        'perfil',
        'lista',
        'textarea',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    public function resultados()
    {
        return $this->hasMany(ResultadoLaboratorio::class);
    }

    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'servicio_rangos',
            'area_rango_id',
            'servicio_id'
        )->withPivot('nombre_variable', 'orden', 'visible')->withTimestamps();
    }
}
