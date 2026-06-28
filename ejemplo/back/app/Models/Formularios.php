<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Formularios extends Model implements AuditableContract
{
    use AuditableTrait,SoftDeletes;

    protected $fillable = [
        'nombre',
        'area_id',
        'html',
    ];
    protected $hidden=[
        'created_at', 'updated_at', 'deleted_at',
    ];

//    ares
    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
