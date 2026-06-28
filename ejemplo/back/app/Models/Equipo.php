<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipo extends Model
{
    use SoftDeletes;

    protected $fillable = ['nombre', 'estado', 'servicio_id'];

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }
}
