<?php

namespace App\Models\Concerns;

use App\Models\TipoProducto;
use App\Models\TipoProductoPadre;

/**
 * Categoría (tipo_producto_padres) y subcategoría (tipo_productos) de un gasto,
 * compartido por Venta (EGRESO) y CajaMovimiento (GASTO). Las relaciones incluyen
 * las categorías borradas para que los gastos antiguos sigan mostrando la suya.
 */
trait TieneCategoriaGasto
{
    public function categoriaGasto()
    {
        return $this->belongsTo(TipoProductoPadre::class, 'tipo_producto_padre_id')->withTrashed();
    }

    public function subcategoriaGasto()
    {
        return $this->belongsTo(TipoProducto::class, 'tipo_producto_id')->withTrashed();
    }

    /**
     * Texto para listados, comprobantes, PDF y Excel: "CATEGORÍA / SUBCATEGORÍA",
     * solo la categoría si no tiene subcategoría, o "Sin categoría" en los gastos
     * anteriores a este campo (con su texto antiguo entre paréntesis, si lo hay).
     */
    public function categoriaGastoTexto(): string
    {
        if ($this->categoriaGasto) {
            return $this->subcategoriaGasto
                ? $this->categoriaGasto->nombre.' / '.$this->subcategoriaGasto->nombre
                : $this->categoriaGasto->nombre;
        }
        $anterior = trim((string) ($this->attributes['categoria'] ?? ''));

        return $anterior !== '' ? "Sin categoría ({$anterior})" : 'Sin categoría';
    }
}
