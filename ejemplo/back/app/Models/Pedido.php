<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pedido extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos';

    protected $fillable = [
        'user_id',
        'unidad_id',
        'fecha_hora',
        'nombre_usuario',
        'comentario',
        'estado',
        'total',
        'modificado',
        'modificacion_detalle',
    ];

    public function detalles()
    {
        return $this->hasMany(DetallePedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function scopeDeUsuario($query)
    {
        return $query->where('user_id', auth()->id());
    }
}
