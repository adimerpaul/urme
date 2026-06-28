<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Diagnostico extends Model implements AuditableContract
{
    use AuditableTrait,SoftDeletes;
    protected $fillable = [
        'servicio',
        'especialidad',
        'cie10',
    ];
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];
}
