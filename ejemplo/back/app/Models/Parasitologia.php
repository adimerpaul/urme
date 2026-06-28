<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class Parasitologia extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'solicitude_id',
        'tipo',

        'olor',
        'color',
        'consistencia',
        'bacterias',
        'otros',

        'descripcion_muestra',
        'descripcion_muestra_1',
        'descripcion_muestra_2',
        'descripcion_muestra_3',

        'sangre_oculta',
        'prueba_rapida_rotavirus',
        'moco_fecal',
        'test_benedict',
        'reaccion',
        'otros_examenes',
        'otros_examenes_otros',
        'code',
        'moco_fecal_positivo',
        'user_id',
        'otros_resultados',
        'user_presentacion_id',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_presentacion' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
//        'created_at',
        'updated_at',
    ];
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
