<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Despacho extends Model
{
    use SoftDeletes;

    protected $table = 'despachos';

    protected $fillable = [
        'pedido_id',
        'unidad_id',
        'user_id',
        'nro',
        'fecha_entrega',
        'solicitante',
        'servicio',
        'personal_recepcion',
        'estado',
        'observaciones',
        'total',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'total'         => 'decimal:2',
    ];

    public function detalles()
    {
        return $this->hasMany(DespachoDetalle::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }
}
