<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class SolicitudRechazada extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;
    protected $table = 'solicitud_rechazadas';
    protected $fillable = [
        'solicitude_id',
        'motivo',
        'fecha_hora',
        'area_id',
        'user_id',
    ];
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];
    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
    }
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
