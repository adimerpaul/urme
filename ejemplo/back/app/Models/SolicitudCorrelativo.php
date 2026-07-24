<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudCorrelativo extends Model
{
    protected $table = 'solicitud_correlativos';

    protected $fillable = [
        'tipo_atencion',
        'anio',
        'mes',
        'ultimo_numero',
    ];

    /**
     * Devuelve el siguiente número del mes (ultimo_numero + 1) sin consumirlo.
     * Si no existe fila para el mes en curso, la crea en 0 (reinicio mensual).
     */
    public static function siguiente(string $tipo, $fecha = null): int
    {
        $fecha = $fecha ? \Carbon\Carbon::parse($fecha) : now();

        $fila = static::firstOrCreate(
            [
                'tipo_atencion' => $tipo,
                'anio' => (int) $fecha->format('Y'),
                'mes' => (int) $fecha->format('m'),
            ],
            ['ultimo_numero' => 0]
        );

        return $fila->ultimo_numero + 1;
    }

    /**
     * Registra el número usado por una solicitud guardada: la secuencia
     * continúa desde ese número (aunque sea menor al actual).
     */
    public static function registrar(string $tipo, int $numero, $fecha = null): void
    {
        $fecha = $fecha ? \Carbon\Carbon::parse($fecha) : now();

        static::updateOrCreate(
            [
                'tipo_atencion' => $tipo,
                'anio' => (int) $fecha->format('Y'),
                'mes' => (int) $fecha->format('m'),
            ],
            ['ultimo_numero' => $numero]
        );
    }
}
