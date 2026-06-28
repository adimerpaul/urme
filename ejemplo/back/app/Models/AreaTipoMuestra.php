<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class AreaTipoMuestra extends Model implements AuditableContract
{
    //        Schema::create('area_tipo_muestras', function (Blueprint $table) {
    //            $table->id();
    //            $table->unsignedBigInteger('area_id');
    //            $table->string('tipo_muestra');
    //            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
    //            $table->softDeletes();
    //            $table->timestamps();
    //        });
    use SoftDeletes, AuditableTrait;
    protected $fillable = [
        'area_id',
        'tipo_muestra',
    ];
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at',
    ];
    function area()
    {
        return $this->belongsTo(Area::class);
    }
    public function solicitudes()
    {
        return $this->belongsToMany(
            Solicitude::class,
            'solitude_pre_analiticas',
            'area_tipo_muestra_id',
            'solicitude_id'
        )->withTimestamps();
    }

    public function servicios()
    {
        return $this->belongsToMany(
            Servicio::class,
            'servicio_area_tipo_muestra',
            'area_tipo_muestra_id',
            'servicio_id'
        )->withTimestamps();
    }
}
