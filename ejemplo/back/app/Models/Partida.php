<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partida extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'grupo_id',
        'num',
        'codigo',
        'nombre',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function subpartidas()
    {
        return $this->hasMany(Subpartida::class);
    }
}
