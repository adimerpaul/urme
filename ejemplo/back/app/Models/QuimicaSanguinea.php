<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class QuimicaSanguinea extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'quimica_sanguineas';

    protected $fillable = [
        'solicitude_id',

        'acido_urico',
        'albumina',
        'proteinas_totales',

        'bilirrubina_total',
        'bilirrubina_directa',
        'bilirrubina_indirecta',

        'got',
        'gpt',
        'fosfatasa_alcalina',
        'ggt',
        'amilasa',

        'glucosa',
        'urea',
        'nus',
        'creatinina',

        'trigliceridos',
        'colesterol_total',
        'hdl_colesterol',
        'ldl_colesterol',
        'vldl_colesterol',

        'ck_total',
        'ck_mb',

        'ferritina',
        'hierro_serico',
        'got_cinetico',
        'gpt_cinetico',
        'hb_glicosilada',
        'hb_a1c',

        'sodio',
        'potasio',
        'cloro',
        'calcio',
        'fosforo',
        'magnesio',
        'ldh',

        'creatinuria_24h',
        'creatinuria_casual',
        'proteinuria_24h',
        'volumen_24h',

        'aso',
        'aso_valor',
        'fr',
        'fr_valor',
        'pcr',
        'pcr_valor',

        'prueba_rapida_vih',
        'rpr',
        'reaccion_widal',
        'dce',

        'observaciones',
        'metodo',
        'equipo',
        'metodo2',
        'equipo2',
        'metodo3',
        'equipo3',
        'equipo_otro',
        'code',
        'globulina',
        'relacion_ag',
        'lipasa',
        'citoquimico_cantidad',
        'tipo_de_muestra',
        'test_embarazo',
        'citoquimico_color',
        'citoquimico_aspecto',
        'citoquimico_glucosa',
        'citoquimico_ph',
        'citoquimico_proteinas_totales',
        'citoquimico_albumina',
        'citoquimico_ldh',
        'citoquimico_globulos_blancos',
        'citoquimico_polimorfonucleares',
        'citoquimico_mononucleares',
        'citoquimico_densidad',

        'reaccion_widal_o',
        'reaccion_widal_o_valor',
        'reaccion_widal_h',
        'reaccion_widal_h_valor',
        'reaccion_widal_a',
        'reaccion_widal_a_valor',
        'reaccion_widal_b',
        'reaccion_widal_b_valor',


        'prueba_rapida_hepatitis_b',
        'prueba_rapida_hepatitis_c',
        'prueba_rapida_chagas',
        'prueba_rapida_sifilis',
        'prueba_rapida_troponina',

        'tolerancia_glucosa_1h',
        'tolerancia_glucosa_2h',
        'tolerancia_glucosa_3h',
        'tolerancia_glucosa_4h',
        'tolerancia_glucosa_5h',
        'tolerancia_hora_1h',
        'tolerancia_hora_2h',
        'tolerancia_hora_3h',
        'tolerancia_hora_4h',
        'tolerancia_hora_5h',
        'user_id',
        'trf',

        'rpr_dilucion',
        'test_embarazo_fum',
        'aso_dilucion',
        'fr_dilucion',
        'pcr_dilucion',
        'microalbuminuria',
        'gasometria_tipo',
        'gasometria_observacion',
        'gasometria_muestra_estado',
        'citoquimico_observaciones',
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
