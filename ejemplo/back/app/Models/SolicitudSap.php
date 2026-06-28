<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SolicitudSap extends Model
{
    use SoftDeletes;

    protected $table = 'solicitudes_sap';

    protected $fillable = [
        'user_id',
        'nro',
        'fecha',
        'unidad_solicitante',
        'apertura_programatica',
        'nro_cite',
        'estado',
        'observaciones',
        'justificacion',
        'total',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'float',
    ];

    public function detalles()
    {
        return $this->hasMany(SolicitudSapDetalle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
