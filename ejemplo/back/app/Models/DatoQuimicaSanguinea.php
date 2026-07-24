<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatoQuimicaSanguinea extends Model
{
    protected $table = 'datos_quimica_sanguinea';

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
            'datos_quimica_sanguinea_prestacion',
            'dato_quimica_sanguinea_id',
            'servicio_id'
        )->withTimestamps();
    }
}
