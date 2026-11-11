<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/**
 * Examen que forma parte de un perfil. Quitarlo borra la fila (sin soft delete):
 * la auditoría guarda el alta, el cambio de orden y la baja.
 */
class ProductoPerfilItem extends Model implements AuditableContract
{
    use AuditableTrait;

    protected $table = 'producto_perfil_items';

    protected $fillable = ['perfil_id', 'producto_id', 'orden'];

    protected $hidden = ['created_at', 'updated_at'];

    public function perfil()
    {
        return $this->belongsTo(Producto::class, 'perfil_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
