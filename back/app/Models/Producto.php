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
     * al menos dos exámenes y todos están activos.
     */
    public function esPerfilVendible(): bool
    {
        return $this->motivoPerfilNoVendible() === null;
    }

    /** Por qué el perfil no se puede vender ni usar en una solicitud; null si se puede. */
    public function motivoPerfilNoVendible(): ?string
    {
        if (! $this->es_perfil) {
            return "{$this->nombre} no es un perfil de exámenes.";
        }
        if ($this->trashed()) {
            return "El perfil {$this->nombre} está dado de baja.";
        }
        // Si quien llama ya cargó el área con es_laboratorio se usa; si no (p. ej.
        // la venta la carga solo con id y nombre), se consulta aparte.
        $area = $this->relationLoaded('tipoProducto') && $this->tipoProducto
            && array_key_exists('es_laboratorio', $this->tipoProducto->getAttributes())
            ? $this->tipoProducto->es_laboratorio
            : TipoProducto::whereKey($this->tipo_producto_id)->value('es_laboratorio');
        if (! $area) {
            return "El perfil {$this->nombre} no pertenece a un área de laboratorio.";
        }

        // Los listados cargan de una vez perfilItems.producto (con los dados de baja) y su área.
        $items = $this->relationLoaded('perfilItems')
            ? $this->perfilItems
            : $this->perfilItems()->with(['producto' => fn ($query) => $query->withTrashed()->with('tipoProducto')])->get();
        if ($items->count() < 2) {
            return "El perfil {$this->nombre} tiene menos de 2 exámenes.";
        }
        foreach ($items as $item) {
            $examen = $item->producto;
            if (! self::examenActivo($examen)) {
                $motivo = match (true) {
                    $examen === null => 'ya no existe',
                    $examen->trashed() => 'está dado de baja',
                    default => 'no pertenece a un área de laboratorio',
                };

                return "El perfil {$this->nombre} no se puede usar: su examen "
                    .($examen?->nombre ?? "#{$item->producto_id}")." {$motivo}.";
            }
        }

        return null;
    }

    /**
     * Exámenes vigentes del perfil, en orden: los que se agregan a la solicitud.
     * Solo tiene sentido en un perfil vendible.
     */
    public function examenesDelPerfil()
    {
        return $this->perfilItems()->with('producto')->get()->pluck('producto')->filter()->values();
    }

    /**
     * Agrega perfil_vendible al listado: 1 si el perfil tiene al menos 2 exámenes
     * y ninguno está dado de baja ni fuera de un área de laboratorio.
     */
    public function scopeConPerfilVendible($query)
    {
        $inactivos = '(SELECT COUNT(*) FROM producto_perfil_items ppi
            LEFT JOIN productos ex ON ex.id = ppi.producto_id
            LEFT JOIN tipo_productos tx ON tx.id = ex.tipo_producto_id AND tx.deleted_at IS NULL
            WHERE ppi.perfil_id = productos.id
              AND (ex.id IS NULL OR ex.deleted_at IS NOT NULL OR tx.id IS NULL OR tx.es_laboratorio = 0))';
        $total = '(SELECT COUNT(*) FROM producto_perfil_items ppi WHERE ppi.perfil_id = productos.id)';

        return $query->selectRaw("productos.es_perfil = 1 AND {$inactivos} = 0 AND {$total} >= 2 AS perfil_vendible");
    }
}
