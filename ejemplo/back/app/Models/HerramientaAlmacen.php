<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class HerramientaAlmacen extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'herramientas_almacen';

    protected $fillable = ['nombre', 'valor'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public static function obtener(string $nombre): ?string
    {
        return static::where('nombre', $nombre)->value('valor');
    }

    public static function pedidosHabilitados(): bool
    {
        $inicio = static::obtener('fecha_inicio_pedido_almacen');
        $fin = static::obtener('fecha_fin_pedido_almacen');

        if (! $inicio || ! $fin) {
            return false;
        }

        $hoy = today()->format('Y-m-d');

        return $hoy >= $inicio && $hoy <= $fin;
    }
}
