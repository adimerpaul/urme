<?php

namespace App\Models;

use App\Support\DescripcionHtml;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Producto extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'codigo', 'nombre', 'nombre_comercial', 'descripcion', 'marca',
        'fabricante_id', 'unidad_id',
        'tipo_producto_id', 'precio', 'precio_seguro',
        'grupo_reporte', 'orden_reporte', 'es_perfil',
    ];

    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    protected $appends = ['descripcion_html'];

    public function getDescripcionHtmlAttribute(): string
    {
        return DescripcionHtml::mostrar($this->descripcion);
    }

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_seguro' => 'decimal:2',
        'es_perfil' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Rige aunque los perfiles estén apagados: protege a los ya creados.
        static::deleting(function (Producto $producto) {
            if ($producto->isForceDeleting()) {
                return;
            }
            $perfiles = $producto->perfiles()->pluck('nombre');
            if ($perfiles->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'producto' => "No se puede dar de baja {$producto->nombre}: forma parte del perfil "
                        .$perfiles->implode(', ').'. Quítelo primero del perfil.',
                ]);
            }
        });
    }

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class);
    }

    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class);
    }

    public function inventarios()
    {
        return $this->hasMany(ProductoInventario::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class);
    }

    public function ventaDetalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function bajaDetalles()
    {
        return $this->hasMany(BajaDetalle::class);
    }

    public function laboratorioDatos()
    {
        return $this->hasMany(ProductoLaboratorioDato::class)->orderBy('orden')->orderBy('id');
    }

    public function laboratorioFormulas()
    {
        return $this->hasMany(ProductoLaboratorioFormula::class)->orderBy('orden')->orderBy('id');
    }

    public function laboratorioValidaciones()
    {
        return $this->hasMany(ProductoLaboratorioValidacion::class)->orderBy('orden')->orderBy('id');
    }

    public function reactivoServicios()
    {
        return $this->hasMany(ServicioLaboratorioReactivo::class);
    }

    /** Filas de composición de este perfil, en orden. */
    public function perfilItems()
    {
        return $this->hasMany(ProductoPerfilItem::class, 'perfil_id')->orderBy('orden')->orderBy('id');
    }

    /** Exámenes vigentes de este perfil, en orden (los dados de baja no salen). */
    public function examenes()
    {
        return $this->belongsToMany(Producto::class, 'producto_perfil_items', 'perfil_id', 'producto_id')
            ->withPivot('orden')
            ->orderByPivot('orden');
    }

    /** Perfiles vigentes que incluyen este examen. */
    public function perfiles()
    {
        return $this->belongsToMany(Producto::class, 'producto_perfil_items', 'producto_id', 'perfil_id')
            ->where('productos.es_perfil', true)
            ->withPivot('orden');
    }

    /**
     * Examen activo para un perfil: existe, no está dado de baja, no es un perfil
     * y su área es de laboratorio.
     */
    public static function examenActivo(?Producto $examen): bool
    {
        return $examen !== null
            && ! $examen->trashed()
            && ! $examen->es_perfil
            && (bool) $examen->tipoProducto?->es_laboratorio;
    }

    /**
     * Un perfil se puede vender si sigue vigente en un área de laboratorio, tiene
     * al menos dos exámenes y todos están activos. Lo usará la venta (Fase 2).
     */
    public function esPerfilVendible(): bool
    {
        if (! $this->es_perfil || $this->trashed() || ! $this->tipoProducto?->es_laboratorio) {
            return false;
        }

        $items = $this->perfilItems()
            ->with(['producto' => fn ($query) => $query->withTrashed()->with('tipoProducto')])
            ->get();

        return $items->count() >= 2
            && $items->every(fn (ProductoPerfilItem $item) => self::examenActivo($item->producto));
    }
}
