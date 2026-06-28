<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Establecimiento extends Model implements AuditableContract
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'nombre',
        'es_publico',
        'es_lab_urbano',
        'es_lab_rural',
        'es_privado',
        'nivel',
        'direccion',
        'telefono_contacto',
        'responsable_laboratorio',
        'telefono_responsable',
        'estado',
        'inicial',
    ];

    protected $casts = [
        'es_publico'    => 'boolean',
        'es_lab_urbano' => 'boolean',
        'es_lab_rural'  => 'boolean',
        'es_privado'    => 'boolean',
    ];

    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];

    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'establecimiento_servicios',
            'establecimiento_id',
            'servicio_id'
        )->withTimestamps();
    }
}
