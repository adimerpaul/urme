<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Internacion extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'internaciones';

    protected $fillable = ['paciente_id', 'seguro_id', 'fecha_ingreso', 'tipo_paciente', 'fecha_alta', 'codigo_hc', 'sala'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $appends = ['dias_internado'];

    public function getDiasInternadoAttribute(): ?int
    {
        if (! $this->fecha_ingreso) {
            return null;
        }
        $hasta = $this->fecha_alta ? Carbon::parse($this->fecha_alta) : now();

        return max(1, Carbon::parse($this->fecha_ingreso)->diffInDays($hasta));
    }

    public function setTipoPacienteAttribute($value): void
    {
        $this->attributes['tipo_paciente'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function setSalaAttribute($value): void
    {
        $this->attributes['sala'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function seguro()
    {
        return $this->belongsTo(Seguro::class);
    }

    public function items()
    {
        return $this->hasMany(InternacionItem::class)->orderBy('created_at');
    }
}
