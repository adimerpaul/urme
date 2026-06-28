<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class CultivoAntibiograma extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'solicitude_id',
        'numero_identificacion',
        'codigo_microbiologia',
        'institucion',
        'cultivo_solicitado',
        'localizacion',
        'servicio',
        'sala',
        'cama',
        'fecha_ingreso',
        'fecha_salida',
        'tincion_gram',
        'conteo_colonia',
        'microorganismo',
        'mecanismo_resistencia',
        'observaciones',
        'antibiograma',
        'code',
        'user_presentacion_id',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_ingreso'      => 'date',
        'fecha_salida'       => 'date',
        'antibiograma'       => 'array',
        'fecha_presentacion' => 'datetime',
    ];

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    function userPresentacion()
    {
        return $this->belongsTo(User::class, 'user_presentacion_id');
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
}
