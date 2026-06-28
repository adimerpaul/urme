<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class SolicitudePreAnaliticaComentario extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'solicitude_id',
        'user_id',
        'comentario',
    ];

    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
