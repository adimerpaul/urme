<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Usuarios
            'Ver Usuarios', 'Crear Usuarios', 'Editar Usuarios', 'Eliminar Usuarios',
            'Gestionar Permisos',

            // Pacientes
            'Ver Pacientes', 'Crear Pacientes', 'Editar Pacientes', 'Eliminar Pacientes',

            // Productos / Farmacia
            'Ver Productos', 'Crear Productos', 'Editar Productos', 'Eliminar Productos',

            // Reportes y PDFs
            'Ver Reportes', 'Imprimir Resultados', 'Exportar Excel',

            // Configuración
            'Ver Configuracion', 'Editar Configuracion',
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
            'password' => bcrypt('admin123Admin'),
        ]);
        $admin->syncPermissions(Permission::all());

        // Milton Enrique Tito Cadima
        User::create([
            'name'     => 'MILTON ENRIQUE TITO CADIMA',
            'username' => 'mtito',
            'email'    => 'mtito@urme.com',
            'ci'       => '12749265',
            'password' => bcrypt('12749265'),
        ]);

        // Daniela Alejandra Peña Valverde
        User::create([
            'name'     => 'DANIELA ALEJANDRA PEÑA VALVERDE',
            'username' => 'dpena',
            'email'    => 'dpena@urme.com',
            'ci'       => '7340511',
            'password' => bcrypt('7340511'),
        ]);

        // Darwin Gabriel Limachi Tito
        User::create([
            'name'     => 'DARWIN GABRIEL LIMACHI TITO',
            'username' => 'dlimachi',
            'email'    => 'dlimachi@urme.com',
            'ci'       => '12644872',
            'password' => bcrypt('12644872'),
        ]);
    }
}
