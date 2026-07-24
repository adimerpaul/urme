<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class SolicitudLaboratorioResultado extends Model implements AuditableContract
{
    use AuditableTrait, SoftDeletes;

    protected $fillable = [
        'solicitud_laboratorio_item_id', 'producto_laboratorio_dato_id',
        'nombre', 'nombre_variable', 'unidad', 'rango_referencia',
        'formula', 'valor', 'orden', 'visible',
    ];

    protected $hidden = ['deleted_at'];

    protected $casts = ['visible' => 'boolean'];

    public function item()
    {
        return $this->belongsTo(SolicitudLaboratorioItem::class, 'solicitud_laboratorio_item_id');
    }
}
