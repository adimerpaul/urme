<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaResultado extends Model
{
    protected $table = 'entrega_resultados';

    protected $fillable = [
        'solicitude_id',
        'area',
        'user_id',
        'fecha_entrega',
        'hora_entrega',
        'observaciones',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
