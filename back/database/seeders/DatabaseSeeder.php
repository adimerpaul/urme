<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Permisos base
        $permisos = [
            'Ver Usuarios', 'Crear Usuarios', 'Editar Usuarios', 'Eliminar Usuarios',
            'Gestionar Permisos',
        ];
        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Admin
        $admin = User::create([
            'name'     => 'ADMINISTRADOR',
            'username' => 'admin',
            'email'    => 'admin@urme.com',
            'ci'       => '00000000',
            'password' => bcrypt('admin123'),
        ]);
        $admin->syncPermissions(Permission::all());

        // Usuario de prueba
        User::create([
            'name'     => 'USUARIO PRUEBA',
            'username' => 'prueba',
            'email'    => 'prueba@urme.com',
            'ci'       => '12345678',
            'password' => bcrypt('123456'),
        ]);
    }
}
