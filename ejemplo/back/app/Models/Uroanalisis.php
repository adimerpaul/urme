<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
use Illuminate\Support\Str;

class Uroanalisis extends Model implements AuditableContract{
    use SoftDeletes, AuditableTrait;

    // Usamos nombre de tabla fijo para evitar plural raro
    protected $table = 'uroanalisis';

    protected $fillable = [
        'solicitude_id',
        'material_ensayo',
        'metodo',
        'cantidad',
        'color',
        'olor',
        'aspecto',
        'reaccion',
        'densidad',
        'espuma',
        'sedimento',
        'celulas_epiteliales',
        'leucocitos',
        'hematies',
        'bacterias',
        'filamento_mucoide',
        'cilindros',
        'celulas',
        'cristales',
        'morfologia_eritrocitaria',
        'proteinas',
        'glucosa',
        'sangre',
        'cetonas',
        'bilirrubina',
        'urobilinogeno',
        'nitritos',
        'observaciones',
        'valor_morfologia',
        'valor_cilindros',
        'valor_celulas',
        'valor_cristales',
        'otros',
        'code',
        'morfologia_eritrocitaria2',
        'valor_morfologia2',
        'user_id',
//        $table->string('cilindros2')->nullable();
//$table->string('celulas_epiteliales2')->nullable();
//$table->string('cristales2')->nullable();
//$table->string('cilindros_valor2')->nullable();
//$table->string('celulas_epiteliales_valor2')->nullable();
//$table->string('cristales_valor2')->nullable();
        'cilindros2',
        'celulas_epiteliales2',
        'cristales2',
        'cilindros_valor2',
        'celulas_epiteliales_valor2',
        'cristales_valor2',
        'user_presentacion_id',
        'fecha_presentacion',
    ];

    protected $casts = [
        'fecha_presentacion' => 'datetime',
    ];
    protected $hidden = [
//        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function solicitude()
    {
        return $this->belongsTo(Solicitude::class);
    }
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = Str::uuid()->toString();
                // sin guiones (32 chars)
                $model->code = str_replace('-', '', $model->code);
            }
        });
    }
    function user()
    {
        return $this->belongsTo(User::class);
    }
    function userPresentacion()
    {
        return $this->belongsTo(User::class, 'user_presentacion_id');
    }
}
