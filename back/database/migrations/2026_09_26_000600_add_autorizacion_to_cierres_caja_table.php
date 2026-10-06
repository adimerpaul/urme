<?php

use App\Models\User;
use App\Support\Permisos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            // Hasta cuándo el usuario puede seguir vendiendo con la caja ya cerrada, y quién lo autorizó.
            $table->dateTime('autorizado_hasta')->nullable()->after('validado_en');
            $table->foreignId('autorizado_por_id')->nullable()->after('autorizado_hasta')->constrained('users');
        });

        Permission::updateOrCreate(
            ['name' => 'Autorizar Ventas Caja Cerrada', 'guard_name' => 'web'],
            ['modulo' => 'Caja', 'descripcion' => Permisos::descripcionDe('Autorizar Ventas Caja Cerrada')],
        );
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::where('username', 'admin')->first()?->givePermissionTo('Autorizar Ventas Caja Cerrada');
    }

    public function down(): void
    {
        Permission::where('name', 'Autorizar Ventas Caja Cerrada')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('autorizado_por_id');
            $table->dropColumn('autorizado_hasta');
        });
    }
};
