<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * La migración anterior solo agregó las columnas grupo_reporte/orden_reporte;
     * en producción quedaron vacías y el informe no agrupa los electrolitos.
     * Se buscan por nombre dentro de los tipos de laboratorio (los ids pueden
     * diferir entre entornos) y solo se completan los que aún no tienen grupo,
     * para no pisar lo configurado a mano desde la pantalla de laboratorio.
     */
    private const ELECTROLITOS = [
        'SODIO' => 1,
        'POTASIO' => 2,
        'CLORO' => 3,
    ];

    public function up(): void
    {
        foreach (self::ELECTROLITOS as $nombre => $orden) {
            DB::table('productos')
                ->join('tipo_productos', 'tipo_productos.id', '=', 'productos.tipo_producto_id')
                ->where('tipo_productos.es_laboratorio', true)
                ->whereRaw('UPPER(TRIM(productos.nombre)) = ?', [$nombre])
                ->where(fn ($query) => $query->whereNull('productos.grupo_reporte')->orWhere('productos.grupo_reporte', ''))
                ->update([
                    'productos.grupo_reporte' => 'Electrolitos',
                    'productos.orden_reporte' => $orden,
                ]);
        }
    }

    public function down(): void
    {
        // Sin reversión: no se distingue lo cargado aquí de lo editado después a mano.
    }
};
