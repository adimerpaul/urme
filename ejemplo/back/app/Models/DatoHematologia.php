<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatoHematologia extends Model
{
    protected $table = 'datos_hematologia';

    protected $fillable = [
        'variable',
        'nombre',
        'seccion',
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
            'datos_hematologia_prestacion',
            'dato_hematologia_id',
            'servicio_id'
        )->withTimestamps();
    }
}
