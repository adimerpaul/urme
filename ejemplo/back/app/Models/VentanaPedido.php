<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VentanaPedido extends Model
{
    use SoftDeletes;

    protected $table = 'ventana_pedidos';

    protected $fillable = [
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'activo',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function vigente(): ?self
    {
        return static::where('activo', true)
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->latest()
            ->first();
    }
}
