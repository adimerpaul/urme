<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class SolicitudePropiedad extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'solicitude_propiedades';

    protected $fillable = [
        'solicitude_id',
        'area_id',
        'campo',
        'valor',
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
}
