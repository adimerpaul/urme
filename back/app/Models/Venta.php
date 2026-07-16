<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Venta extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'ventas';

    protected $fillable = [
        'user_id', 'paciente_id', 'doctor_id', 'seguro_id', 'cliente', 'fecha_hora',
        'tipo_pago', 'comentario', 'estado', 'total', 'pago', 'cambio',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'total' => 'decimal:2',
        'pago' => 'decimal:2',
        'cambio' => 'decimal:2',
    ];

    public function setClienteAttribute($value): void
    {
        $this->attributes['cliente'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function seguro()
    {
        return $this->belongsTo(Seguro::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }
}
