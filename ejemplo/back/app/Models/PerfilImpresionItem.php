<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerfilImpresionItem extends Model
{
    use SoftDeletes;

    protected $table = 'perfil_impresion_items';

    protected $fillable = [
        'perfil_id',
        'area_rango_id',
        'seccion',
        'columna',
        'orden',
        'mostrar_en_paciente',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function perfil()
    {
        return $this->belongsTo(PerfilImpresion::class, 'perfil_id');
    }

    public function areaRango()
    {
        return $this->belongsTo(\App\Models\AreaRango::class, 'area_rango_id');
    }
}
