<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstablecimientoServicio extends Model
{
    use SoftDeletes;

    protected $table = 'establecimiento_servicios';

    protected $fillable = [
        'establecimiento_id',
        'servicio_id',
    ];
}
