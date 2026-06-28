<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class SolicitudeFormulario extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'solicitude_formularios';

    protected $fillable = [
        'solicitude_id',
        'formulario_id',
        'area_id',
        'nombre',
        'html',
        'user_id',
    ];

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function solicitud()
    {
        return $this->belongsTo(Solicitude::class, 'solicitude_id');
    }

    public function formulario()
    {
        return $this->belongsTo(Formularios::class, 'formulario_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
    function user()
    {
        return $this->belongsTo(User::class);
    }
}
