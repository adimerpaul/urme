<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agrupacion extends Model
{
    protected $table = 'agrupaciones';

    protected $fillable = [
        'nombre',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function prestaciones()
    {
        return $this->belongsToMany(
            Servicio::class,
            'agrupacion_prestacion',
            'agrupacion_id',
            'servicio_id'
        )->withTimestamps();
    }
}
