<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Reactivo extends Model implements Auditable
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = ['codigo', 'nombre', 'unidad', 'stock_actual', 'stock_minimo', 'estado', 'descripcion'];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $casts = ['stock_actual' => 'decimal:3', 'stock_minimo' => 'decimal:3'];

    public function setCodigoAttribute($value): void
    {
        $this->attributes['codigo'] = $value ? mb_strtoupper($value) : null;
    }

    public function setNombreAttribute($value): void
    {
        $this->attributes['nombre'] = mb_strtoupper($value);
    }

    /**
     * El signo micro se guarda siempre como U+00B5 (la mu griega U+03BC se
     * reemplaza) y no pasa a mayúsculas: mb_strtoupper('µL') daría 'ΜL', con
     * la M griega, que se confunde con ML. El resto va en mayúsculas como antes.
     */
    public function setUnidadAttribute($value): void
    {
        $value = str_replace("\u{03BC}", "\u{00B5}", (string) $value);

        $this->attributes['unidad'] = preg_replace_callback(
            '/[^\x{00B5}]+/u',
            fn (array $tramo) => mb_strtoupper($tramo[0]),
            $value
        );
    }

    public function setEstadoAttribute($value): void
    {
        $this->attributes['estado'] = mb_strtoupper($value);
    }

    public function setDescripcionAttribute($value): void
    {
        $this->attributes['descripcion'] = $value ? mb_strtoupper($value) : null;
    }

    public function servicios()
    {
        return $this->hasMany(ServicioLaboratorioReactivo::class);
    }
}
