<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subpartida extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'partida_id',
        'num',
        'codigo',
        'nombre',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function partida()
    {
        return $this->belongsTo(Partida::class);
    }

    public function almacenItems()
    {
        return $this->hasMany(AlmacenItem::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
