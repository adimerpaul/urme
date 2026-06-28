<?php

// app/Models/Consentimiento.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Consentimiento extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'paciente_id',
        'solicitude_id',
        'fecha_recepcion',
        'hora_recepcion',
        'fecha_solicitud',

        'nombre_completo',
        'fecha_nac',
        'genero',
        'edad',
        'ci',
        'telefono',
        'direccion',

        'discapacidad',
        'discapacidad_cual',
        'discapacidad_otro',

        'embarazo',
        'fum',
        'sem_gest',

        'medicamento',
        'tratamiento',

        'condicion',
        'etapa_gestacion',

        'tipo',
        'declarante_nombre',
        'declarante_condicion',
        'declarante_condicion_otro',
        'fecha_consentimiento',
        'm_orina',
        'hr_recoleccion_orina',
        'm_liquidos',
        'm_esputo',
        'm_secreciones',
        'm_heces',
        'hr_recoleccion_heces',
        'observaciones',
        'hr_recoleccion',

        'user_id',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $casts = [
        'discapacidad'  => 'integer',
        'embarazo'      => 'integer',
        'medicamento'   => 'integer',
        'm_orina'       => 'integer',
        'm_liquidos'    => 'integer',
        'm_esputo'      => 'integer',
        'm_secreciones' => 'integer',
        'm_heces'       => 'integer',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
    }
}
