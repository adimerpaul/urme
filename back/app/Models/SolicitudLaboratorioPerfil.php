<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Perfil tal como quedó en la solicitud: nombre y precio copiados del catálogo
 * al guardar. Sus exámenes son los ítems con solicitud_laboratorio_perfil_id.
 */
class SolicitudLaboratorioPerfil extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'solicitud_laboratorio_perfiles';

    protected $fillable = ['solicitude_id', 'perfil_producto_id', 'nombre', 'precio', 'orden'];

    protected $hidden = ['deleted_at'];

    protected $casts = ['precio' => 'decimal:2'];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
    }

    public function perfilProducto()
    {
        return $this->belongsTo(Producto::class, 'perfil_producto_id');
    }

    public function items()
    {
        return $this->hasMany(SolicitudLaboratorioItem::class)->orderBy('orden')->orderBy('id');
    }
}
