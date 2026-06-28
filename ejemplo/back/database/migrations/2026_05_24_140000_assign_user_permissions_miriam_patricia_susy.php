<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        // Asegura que los permisos existen (idempotente)
        $perms = [
            'Solicitudes',
            'Analitica',
            'Formularios',
            'Consentimientos',
            'Pacientes',
        ];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Helper para asignar permisos por coincidencia parcial de nombre
        $assign = function (string $needle, array $permNames) {
            $usuarios = User::where('name', 'like', '%'.$needle.'%')->get();
            foreach ($usuarios as $u) {
                foreach ($permNames as $perm) {
                    if (! $u->hasPermissionTo($perm)) {
                        $u->givePermissionTo($perm);
                    }
                }
            }
        };

        // Dra. MIRIAM BARRIENTOS — Uroanalisis y Parasitologia
        // (ambas estan dentro del permiso 'Analitica'; agregamos tambien
        // Solicitudes para ver el listado)
        $assign('MIRIAM', ['Analitica', 'Solicitudes']);
        $assign('BARRIENTOS', ['Analitica', 'Solicitudes']);

        // Dra. PATRICIA CONDARCO — Formularios
        $assign('PATRICIA', ['Formularios', 'Analitica', 'Solicitudes']);
        $assign('CONDARCO', ['Formularios', 'Analitica', 'Solicitudes']);

        // SUSY REGINA LOPEZ FERNANDEZ — Consentimientos, Pacientes,
        // Solicitudes (incluye Uroanalisis y Parasitologia via Analitica)
        $assign('SUSY', ['Consentimientos', 'Pacientes', 'Solicitudes', 'Analitica']);
        $assign('LOPEZ FERNANDEZ', ['Consentimientos', 'Pacientes', 'Solicitudes', 'Analitica']);
    }

    public function down(): void
    {
        // No revertimos los permisos (irreversible por seguridad).
        // Si se necesita rollback, hacerlo manualmente desde la UI.
    }
};
