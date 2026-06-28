<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class PanelRespiratorio extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'solicitude_id',
        'vrs_ab',
        'influenza_b',
        'influenza_a',
        'sars_cov_2',
        'streptococcus_pyogenes',
        'adenovirus',
        'rhinovirus',
        'coronavirus_229e_oc43',
        'parainfluenza_1_2',
        'coronavirus_nl63_hku1',
        'parainfluenza_3_4',
        'haemophilus_influenzae',
        'bordetella_pertussis',
        'streptococcus_pneumoniae',
        'bocavirus',
        'mycoplasma_pneumoniae',
        'metapneumovirus',
        'enterovirus',
        'legionella_pneumophila',
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
        $maxNumeracion = PanelRespiratorio::max('numeracion');
        if ($maxNumeracion) {
            $number = intval(substr($maxNumeracion, 0, strpos($maxNumeracion, 'VR'))) + 1;
        } else {
            $number = 1;
        }
        $year = date('y');
        return str_pad($number, 4, '0', STR_PAD_LEFT) . 'VR-' . $year;
    }
}
