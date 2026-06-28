<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
class User extends Authenticatable implements Auditable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'role',
        'avatar',
        'firma',
        'mostrar_firma',
        'sello',
        'mostrar_sello',
        'email',
        'celular',
        'password',
        'area_id',
        'establecimiento_id',
        'unidad_id',
        'ci',
        'max_pedidos',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'mostrar_firma'     => 'boolean',
            'mostrar_sello'     => 'boolean',
        ];
    }
    function establecimiento(){
        return $this->belongsTo(Establecimiento::class);
    }
    function area(){
        return $this->belongsTo(Area::class);
    }
    function unidad(){
        return $this->belongsTo(Unidad::class);
    }
    function subpartidas(){
        return $this->belongsToMany(Subpartida::class);
    }
}
