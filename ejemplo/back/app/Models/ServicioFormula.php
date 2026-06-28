<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicioFormula extends Model
{
    protected $fillable = [
        'servicio_id',
        'label',
        'nombre_variable',
        'formula',
        'unidad',
        'orden',
    ];

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }
}
