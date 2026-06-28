<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class PapilomaHumano extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'solicitude_id',
        'hpv_alto_riesgo',
        'hpv_16',
        'hpv_18',
        'hpv_45',
        'hpv_26',
        'hpv_31',
        'hpv_33',
        'hpv_35',
        'hpv_39',
        'hpv_51',
        'hpv_52',
        'hpv_53',
        'hpv_56',
        'hpv_58',
        'hpv_59',
        'hpv_66',
        'hpv_67',
        'hpv_68',
        'hpv_69',
        'hpv_70',
        'hpv_73',
        'hpv_82',
        'hpv_97',
        'metodo',
        'equipo',
        'numeracion',
        'codigo_muestra',
        'observaciones',
        'code',
        'user_id',
        'user_presentacion_id',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_presentacion' => 'datetime',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
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
//generateNumeracion
    function generateNumeracion()
    {
        $maxNumeracion = PapilomaHumano::max('numeracion');
        if ($maxNumeracion) {
            $number = intval(substr($maxNumeracion, 0, strpos($maxNumeracion, 'P'))) + 1;
        } else {
            $number = 1;
        }
        $year = date('y');
        return str_pad($number, 4, '0', STR_PAD_LEFT) . 'P-' . $year;
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
