<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Solicitude extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        // relaciones
        'paciente_id',
        'doctor_id',

        // cabecera solicitud
        'codigo_solicitud',
        'tipo_atencion',
        'tipo_otro',
        'fecha_solicitud',
        'hora_solicitud',
        'establecimiento_salud',
        'zona_establecimiento',
        'diagnostico_clinico',
        'diagnostico_select',
        'estado',                  // CREADO, ATENDIENDO, FINALIZADO
        'codigo',
        'nro_registro',

        // copia de datos del paciente
        'paciente_nombre',
        'paciente_ci',
        'paciente_telefono',
        'paciente_direccion',
        'paciente_fecha_nac',
        'paciente_genero',
        'paciente_edad',
        'paciente_discapacidad',
        'paciente_discapacidad_cual',
        'paciente_discapacidad_otro',
        'paciente_embarazo',
        'paciente_fum',
        'paciente_sem_gest',

        // copia de datos del doctor
        'doctor_nombre',
        'doctor_especialidad',
        'doctor_ci',
        'doctor_telefono',
        'doctor_email',
        'doctor_registro',

        // fechas de flujo
        'fecha_pre_analitica',
        'fecha_creacion',
        'fecha_envio_analitica',
        'fecha_finalizacion',
        'establecimiento_id',

        // usuario que crea / atiende
        'user_id',
        'user_preanalitica_id',
        'user_analitica_id',
        'sala',
        'unidad_solicitante_id',
        'cama',

        // ---- NUEVOS CAMPOS: calidad de muestra + equipo ----
        'muestra_sangre_entera',
        'muestra_coagulo',
        'muestra_volumen',
        'muestra_identificacion',
        'muestra_equipo',
//        $table->string('muestra_rechazada')->nullable();
//$table->string('muestra_observacion')->nullable();
        'muestra_rechazada',
        'muestra_observacion',
        'numero_factura',
        'iniciales',
        'establecimiento_origen_id',
        'motivo_rechazo',
        'tipo_paciente_externo',
        'autorizado_por',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected function toUpperValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        return mb_strtoupper($text, 'UTF-8');
    }

    public function setPacienteNombreAttribute($value): void
    {
        $this->attributes['paciente_nombre'] = $this->toUpperValue($value);
    }

    public function setPacienteDireccionAttribute($value): void
    {
        $this->attributes['paciente_direccion'] = $this->toUpperValue($value);
    }

    public function setPacienteTelefonoAttribute($value): void
    {
        $this->attributes['paciente_telefono'] = $this->toUpperValue($value);
    }

    public function setPacienteGeneroAttribute($value): void
    {
        $this->attributes['paciente_genero'] = $this->toUpperValue($value);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function unidadSolicitante()
    {
        return $this->belongsTo(UnidadSolicitante::class, 'unidad_solicitante_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userPreanalitica()
    {
        return $this->belongsTo(User::class, 'user_preanalitica_id');
    }

    public function servicios()
    {
        return $this->belongsToMany(\App\Models\Servicio::class, 'servicio_solicitudes', 'solicitude_id', 'servicio_id')
            ->withPivot(['id', 'area_id', 'precio', 'nombre', 'realizado', 'realizado_por', 'fue_recogido'])
            ->wherePivotNull('deleted_at'); // por SoftDeletes en pivot
    }

    public function servicioSolicitudes()
    {
        return $this->hasMany(ServicioSolicitude::class, 'solicitude_id');
    }

    public function preAnaliticaMuestras()
    {
        return $this->hasMany(SolitudePreAnalitica::class, 'solicitude_id');
    }

    public function userAnalitica()
    {
        return $this->belongsTo(User::class, 'user_analitica_id');
    }

    public function entregaResultados()
    {
        return $this->hasMany(\App\Models\EntregaResultado::class, 'solicitude_id');
    }

    public function resultados()
    {
        return $this->hasMany(ResultadoLaboratorio::class);
    }
    public function propiedades()
    {
        return $this->hasMany(SolicitudePropiedad::class);
    }
    function solicitudeFormularios(){
        return $this->hasMany(SolicitudeFormulario::class);
    }
    public function hematologia()
    {
        return $this->hasOne(Hematologia::class);
    }

    public function quimicaSanguinea()
    {
        return $this->hasOne(QuimicaSanguinea::class);
    }

    public function uroanalisis()
    {
        return $this->hasOne(Uroanalisis::class);
    }
    function parasitologia()
    {
        return $this->hasOne(Parasitologia::class);
    }
    public function papilomaHumano()
    {
        return $this->hasOne(PapilomaHumano::class);
    }
    public function panelRespiratorio()
    {
        return $this->hasOne(PanelRespiratorio::class);
    }
    public function panelSexual()
    {
        return $this->hasOne(PanelSexual::class);
    }
    public function cultivoAntibiograma()
    {
        return $this->hasOne(CultivoAntibiograma::class);
    }
    public function consentimiento()
    {
        return $this->hasOne(Consentimiento::class, 'solicitude_id');
    }
//inmunologia
    public function solicitudRechazadas()
    {
        return $this->hasMany(SolicitudRechazada::class);
    }

    public function preAnaliticaComentarios()
    {
        return $this->hasMany(SolicitudePreAnaliticaComentario::class, 'solicitude_id')
            ->orderByDesc('created_at');
    }

}
