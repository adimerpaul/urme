<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SolicitudLaboratorioItem extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = ['solicitude_id', 'producto_id', 'solicitud_laboratorio_perfil_id', 'producto_nombre', 'precio', 'orden'];

    protected $hidden = ['deleted_at'];

    protected $casts = ['precio' => 'decimal:2'];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /** Perfil de la solicitud del que vino el examen (null = examen suelto). */
    public function perfil()
    {
        return $this->belongsTo(SolicitudLaboratorioPerfil::class, 'solicitud_laboratorio_perfil_id');
    }

    public function resultados()
    {
        return $this->hasMany(SolicitudLaboratorioResultado::class)->orderBy('orden')->orderBy('id');
    }
}
