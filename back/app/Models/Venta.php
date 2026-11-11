<?php

namespace App\Models;

use App\Models\Concerns\TieneCategoriaGasto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Venta extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes, TieneCategoriaGasto;

    protected $table = 'ventas';

    protected $fillable = [
        'user_id', 'cobrado_por_id', 'tipo_movimiento', 'tipo_paciente', 'servicio', 'tipo_producto_padre_id', 'tipo_producto_id', 'paciente_id', 'doctor_id', 'seguro_id', 'cliente', 'fecha_hora',
        'fecha_hora_cobro',
        'tipo_pago', 'comentario', 'con_factura', 'numero_factura', 'estado', 'total', 'total_original', 'pago', 'cambio',
        'monto_efectivo', 'monto_qr',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'con_factura' => 'boolean',
        'fecha_hora_cobro' => 'datetime',
        'total' => 'decimal:2',
        'total_original' => 'decimal:2',
        'pago' => 'decimal:2',
        'cambio' => 'decimal:2',
        'monto_efectivo' => 'decimal:2',
        'monto_qr' => 'decimal:2',
    ];

    /** Gasto de caja (refresco, periódico, etc.): dinero que sale de caja, no que entra. */
    public function esEgreso(): bool
    {
        return $this->tipo_movimiento === 'EGRESO';
    }

    /**
     * Filtra por tipo padre: las ventas con algún producto cuyo tipo cuelga de
     * ese padre y los gastos cuya categoría es ese padre.
     */
    public function scopeDeTipoPadre($query, $padreId)
    {
        return $query->where(function ($q) use ($padreId) {
            $q->where(fn ($ingreso) => $ingreso->where('tipo_movimiento', '!=', 'EGRESO')
                ->whereHas('detalles.producto', fn ($producto) => $producto->withTrashed()
                    ->whereHas('tipoProducto', fn ($tipo) => $tipo->withTrashed()->where('tipo_producto_padre_id', $padreId))))
                ->orWhere(fn ($gasto) => $gasto->where('tipo_movimiento', 'EGRESO')->where('tipo_producto_padre_id', $padreId));
        });
    }

    /**
     * Agrega a cada venta el atributo tipos_padre ([{id, nombre, color}]): los
     * tipos padre de sus productos, o la categoría en los gastos. Una sola
     * consulta para toda la colección.
     */
    public static function cargarTiposPadre(Collection $ventas): void
    {
        $ids = $ventas->reject(fn (Venta $venta) => $venta->esEgreso())->pluck('id')->all();

        $porVenta = $ids ? DB::table('venta_detalles as d')
            ->join('productos as p', 'p.id', '=', 'd.producto_id')
            ->join('tipo_productos as t', 't.id', '=', 'p.tipo_producto_id')
            ->join('tipo_producto_padres as tp', 'tp.id', '=', 't.tipo_producto_padre_id')
            ->whereIn('d.venta_id', $ids)
            ->whereNull('d.deleted_at')
            ->select('d.venta_id', 'tp.id', 'tp.nombre', 'tp.color', 'tp.orden')
            ->distinct()
            ->orderBy('tp.orden')
            ->orderBy('tp.nombre')
            ->get()
            ->groupBy('venta_id') : collect();

        foreach ($ventas as $venta) {
            if ($venta->esEgreso()) {
                $padre = $venta->categoriaGasto;
                $tipos = $padre ? [['id' => $padre->id, 'nombre' => $padre->nombre, 'color' => $padre->color]] : [];
            } else {
                $tipos = ($porVenta[$venta->id] ?? collect())
                    ->map(fn ($fila) => ['id' => (int) $fila->id, 'nombre' => $fila->nombre, 'color' => $fila->color])
                    ->values()->all();
            }
            $venta->setAttribute('tipos_padre', $tipos);
        }
    }

    /** Tipos padre separados por coma, para el Excel y el PDF. */
    public function tiposPadreTexto(): string
    {
        return collect($this->getAttribute('tipos_padre') ?? [])->pluck('nombre')->implode(', ');
    }

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
