<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Paciente extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'fecha_recepcion', 'hora_recepcion',
        'codigo',
        'nombre_completo', 'fecha_nac', 'genero', 'edad',
        'ci', 'telefono', 'direccion',
        'discapacidad', 'discapacidad_cual', 'discapacidad_otro',
        'embarazo', 'fum', 'sem_gest'
    ];

    public static function generarCodigo(?string $nombreCompleto, $fechaNac): string
    {
        $iniciales = '';
        if (!empty($nombreCompleto)) {
            $palabras = preg_split('/\s+/', mb_strtoupper(trim($nombreCompleto), 'UTF-8'));
            foreach ($palabras as $palabra) {
                if ($palabra !== '') {
                    $iniciales .= mb_substr($palabra, 0, 1, 'UTF-8');
                }
            }
        }

        $fecha = '';
        if (!empty($fechaNac)) {
            try {
                $fecha = \Carbon\Carbon::parse($fechaNac)->format('dmY');
            } catch (\Exception $e) {
                $fecha = '';
            }
        }

        return $iniciales . $fecha;
    }

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    protected function toUpperValue($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return '';
        }

        return mb_strtoupper($text, 'UTF-8');
    }

    public function setNombreCompletoAttribute($value): void
    {
        $this->attributes['nombre_completo'] = $this->toUpperValue($value);
    }

    public function setDireccionAttribute($value): void
    {
        $this->attributes['direccion'] = $this->toUpperValue($value);
    }

    public function setTelefonoAttribute($value): void
    {
        $this->attributes['telefono'] = $this->toUpperValue($value);
    }

    public function setGeneroAttribute($value): void
    {
        $this->attributes['genero'] = $this->toUpperValue($value);
    }
}
