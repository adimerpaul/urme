<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CierreCaja extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'cierres_caja';

    protected $fillable = [
        'user_id', 'fecha', 'monto_sistema', 'monto', 'detalle_efectivo', 'diferencia',
        'cantidad_ventas', 'fecha_hora', 'comentario', 'modificado_en',
        'validado_por_id', 'validado_en', 'autorizado_hasta', 'autorizado_por_id',
        // Arqueo de efectivo (fórmula nueva). En los cierres anteriores quedan en null.
        'formula', 'ingresos_total', 'efectivo_ingresos', 'qr_total', 'qr_cantidad', 'otros_total',
        'otros_cantidad', 'gastos_efectivo', 'gastos_cantidad', 'efectivo_esperado', 'diferencia_efectivo',
    ];

    /** Importes que solo viajan con 'Ver Montos Caja'. */
    public const CAMPOS_MONTO = [
        'monto_sistema', 'diferencia', 'ingresos_total', 'efectivo_ingresos', 'qr_total', 'otros_total',
        'gastos_efectivo', 'efectivo_esperado', 'diferencia_efectivo',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha' => 'date:Y-m-d',
        'fecha_hora' => 'datetime',
        'modificado_en' => 'datetime',
        'validado_en' => 'datetime',
        'autorizado_hasta' => 'datetime',
        'monto_sistema' => 'decimal:2',
        'monto' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'detalle_efectivo' => 'array',
        'ingresos_total' => 'decimal:2',
        'efectivo_ingresos' => 'decimal:2',
        'qr_total' => 'decimal:2',
        'otros_total' => 'decimal:2',
        'gastos_efectivo' => 'decimal:2',
        'efectivo_esperado' => 'decimal:2',
        'diferencia_efectivo' => 'decimal:2',
    ];

    protected $appends = ['puede_modificar', 'validado', 'autorizado', 'formula_anterior'];

    /** Cierre hecho con la fórmula anterior (comparaba el efectivo con el total, QR incluido). */
    public function getFormulaAnteriorAttribute(): bool
    {
        return $this->formula === null;
    }

    public function setComentarioAttribute($value): void
    {
        $this->attributes['comentario'] = $value !== null ? mb_strtoupper($value) : $value;
    }

    /** El cierre admite una sola corrección; después, o una vez validado, queda bloqueado. */
    public function getPuedeModificarAttribute(): bool
    {
        return $this->modificado_en === null && $this->validado_en === null;
    }

    /** Con la caja cerrada, el usuario sigue vendiendo mientras dure la autorización. */
    public function getAutorizadoAttribute(): bool
    {
        return $this->autorizado_hasta !== null && $this->autorizado_hasta->isFuture();
    }

    public function getValidadoAttribute(): bool
    {
        return $this->validado_en !== null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por_id');
    }

    public function validadoPor()
    {
        return $this->belongsTo(User::class, 'validado_por_id');
    }
}
