<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HerramientaUsuario extends Model
{
    use SoftDeletes;

    protected $table = 'herramientas_usuario';

    protected $fillable = ['nombre', 'valor'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public static function obtener(string $nombre): ?string
    {
        return static::where('nombre', $nombre)->value('valor');
    }

    public static function registroHabilitado(): bool
    {
        $inicio = static::obtener('fecha_inicio_registro');
        $fin    = static::obtener('fecha_fin_registro');

        if (! $inicio || ! $fin) {
            return false;
        }

        $ahora = now();

        return $ahora->gte(\Carbon\Carbon::parse($inicio))
            && $ahora->lte(\Carbon\Carbon::parse($fin));
    }
}
