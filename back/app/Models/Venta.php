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
        'user_id', 'cobrado_por_id', 'paciente_id', 'doctor_id', 'seguro_id', 'cliente', 'fecha_hora',
        'fecha_hora_cobro',
        'tipo_pago', 'comentario', 'estado', 'total', 'total_original', 'pago', 'cambio',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'fecha_hora_cobro' => 'datetime',
        'total' => 'decimal:2',
        'total_original' => 'decimal:2',
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

    public function cobradoPor()
    {
        return $this->belongsTo(User::class, 'cobrado_por_id');
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
