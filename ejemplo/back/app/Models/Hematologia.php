<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class Hematologia extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'solicitude_id',

        'globulos_rojos',
        'globulos_blancos',
        'plaquetas',
        'hemoglobina',
        'hematocrito',
        'vcm',
        'hbcm',
        'chcm',
        'leucocitos_totales',

        'basofilos_porcentaje',
        'basofilos_absoluto',
        'eosinofilos_porcentaje',
        'eosinofilos_absoluto',
        'cayados_porcentaje',
        'cayados_absoluto',
        'segmentados_porcentaje',
        'segmentados_absoluto',
        'linfocitos_porcentaje',
        'linfocitos_absoluto',
        'monocitos_porcentaje',
        'monocitos_absoluto',
        'blastos_porcentaje',
        'blastos_absoluto',
        'metamielocitos_porcentaje',
        'metamielocitos_absoluto',
        'eritroblastos_porcentaje',
        'eritroblastos_absoluto',

        'morfologia_eritrocitos',

        'tiempo_protrombina',
        'actividad_protrombina',
        'inr',
        'aptt',
        'fibrinogeno',
        'dimeros_d',
        'ves',
        'ipr',
        'ipr2',
        'rc',

        'grupo_sanguineo',
        'factor_rh',

        'metodo',
        'equipo',
        'equipo_otro',
        'code',
        'serie_roja',
        'serie_blanca',
        'serie_plaqueta',
        'hemograma_metodo',
        'hemograma_equipo',
        'coagulograma_metodo',
        'coagulograma_equipo',
        'observaciones',
        'user_id',
        'user_presentacion_id',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_presentacion' => 'datetime',
    ];

    protected $hidden = [
//        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = Str::uuid()->toString();
                // sin guiones (32 chars)
                $model->code = str_replace('-', '', $model->code);
            }
        });
    }
    function user()
    {
        return $this->belongsTo(User::class);
    }
    function userPresentacion()
    {
        return $this->belongsTo(User::class, 'user_presentacion_id');
    }
}
