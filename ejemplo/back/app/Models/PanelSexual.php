<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class PanelSexual extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'panel_sexuales';

    protected $fillable = [
        'solicitude_id',
        'chlamydia_trachomatis',
        'mycoplasma_genitalium',
        'neisseria_gonorrhoeae',
        'trichomonas_vaginalis',
        'ureaplasma_urealyticum',
        'ureaplasma_parvum',
        'mycoplasma_hominis',
        'hsv_1',
        'hsv_2',
        'treponema_pallidum',
        'candida_albicans',
        'gardnerella_vaginalis',
        'observaciones',
        'code',
        'metodo',
        'equipo',
        'numeracion',
        'codigo_muestra',
        'user_id',
        'user_presentacion_id',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_presentacion' => 'datetime',
    ];

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];
    function user()
    {
        return $this->belongsTo(User::class);
    }
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
    function generateNumeracion(){
        $maxNumeracion = PanelSexual::max('numeracion');
        if ($maxNumeracion) {
            $number = intval(substr($maxNumeracion, 0, strpos($maxNumeracion, 'PS'))) + 1;
        } else {
            $number = 1;
        }
        $year = date('y');
        return str_pad($number, 4, '0', STR_PAD_LEFT) . 'PS-' . $year;
    }
}
