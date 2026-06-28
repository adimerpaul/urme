<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ClasificadorPresupuestarioSeeder::class);

        $establecimiento = \App\Models\Establecimiento::create([
            'nombre' => 'Hospital General',
            'direccion' => 'San Felipe Y 6 De Octubre Oruro, Bolivia',
            'telefono_contacto' => '2 5275405',
            'responsable_laboratorio' => 'Dr. House',
            'telefono_responsable' => '555-1111',
            'tipo' => 'Privado',
            'nivel' => 'Terciario',
            'estado' => 'ACTIVO',
        ]);
        $establecimiento2 = \App\Models\Establecimiento::create([
            'nombre' => 'Centro De Salud Chiripujio',
            'direccion' => 'Calle Falsa 123 La Paz, Bolivia',
            'telefono_contacto' => '2 1234567',
            'responsable_laboratorio' => 'Dra. Smith',
            'telefono_responsable' => '555-2222',
            'tipo' => 'Publico',
            'nivel' => 'Primario',
            'estado' => 'ACTIVO',
        ]);



//        $doctor = Doctor::create([
//            'nombre' => 'Dr. Juan Perez',
//            'especialidad' => 'Cardiologia',
//            'ci' => '12345678',
//            'telefono' => '555-1234',
//            'email' => 'perez@gmail.com',
//            'registro' => 'REG-001',
//            'estado' => 'ACTIVO',
//            'establecimiento_id' => 1,
//        ]);
////        docto 2 establecimiento
//        $doctor2 = Doctor::create([
//            'nombre' => 'Dra. Ana Gomez',
//            'especialidad' => 'Pediatria',
//            'ci' => '87654321',
//            'telefono' => '555-5678',
//            'email' => 'ana@gmail.com',
//            'registro' => 'REG-002',
//            'estado' => 'ACTIVO',
//            'establecimiento_id' => 2,
//        ]);
        $paciente = \App\Models\Paciente::create([
            'fecha_recepcion' => '2024-01-15',
            'hora_recepcion' => '10:30:00',
            'nombre_completo' => 'Maria Lopez',
            'fecha_nac' => '1990-05-20',
            'genero' => 'F',
            'edad' => 33,
            'ci' => '87654321',
            'telefono' => '555-5678',
            'direccion' => 'Calle Falsa 123',
            'discapacidad' => false,
            'embarazo' => false,
        ]);

//            paciente fake datos 10000 fake
//        for ($i = 0; $i < 100; $i++) {
//            \App\Models\Paciente::create([
//                'fecha_recepcion' => now()->subDays(rand(0, 365))->toDateString(),
//                'hora_recepcion' => now()->subMinutes(rand(0, 1440))->toTimeString(),
//                'nombre_completo' => 'Paciente ' . ($i + 1),
//                'fecha_nac' => now()->subYears(rand(1, 100))->toDateString(),
//                'genero' => ['F', 'M', 'OTRO'][array_rand(['F', 'M', 'OTRO'])],
//                'edad' => rand(1, 100),
//                'ci' => strval(rand(1000000, 99999999)),
//                'telefono' => '555-' . rand(1000, 9999),
//                'direccion' => 'Direccion ' . ($i + 1),
//                'discapacidad' => (bool)rand(0, 1),
//                'embarazo' => (bool)rand(0, 1),
//            ]);
//        }
//        diagnosticos_202512130542.sql
//        doctors_202512150411.sql
        $path = database_path('seeders/doctors_202512150411.sql');
        $sql = file_get_contents($path);
        \DB::unprepared($sql);

        $path = database_path('seeders/diagnosticos_202512130542.sql');
        $sql = file_get_contents($path);
        \DB::unprepared($sql);
        $this->call([
            UnidadSolicitanteSeeder::class,
            ServiciosSeeder::class,
            AreaTipoMuestraSeeder::class,
            AreaRangoSeeder::class,
            AreaRangoQuimicaSeeder::class,
            PerfilImpresionSeeder::class,
//            AreaRangoMicrobiologiaSeeder::class,
            AreaRangoUroanalisisSeeder::class,
            FormularioSeeder::class,
        ]);

        $userAdmin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'role' => 'Administrador',
            'avatar' => 'default.png',
            'email' => '',
            'password' => 'admin123Admin',
            'establecimiento_id' => 1,
            'area_id' => 1,
        ]);
        $iveth = User::create([
            'name'  => 'Iveth Carrasco Calzaya',
            'username' => 'iveth',
            'role'  => 'Paciente',
            'avatar' => 'default.png',
            'email' => '',
            'password' => '1234567', // o Hash::make('3517836')
            'establecimiento_id' => 1,
            'area_id' => 5,
        ]);

        $nely = User::create([
            'name'  => 'Nely Mena Rodríguez',
            'username' => 'nely',
            'role'  => 'Paciente',
            'avatar' => 'default.png',
            'email' => '',
            'password' => '1234567', // o Hash::make('7286118')
            'establecimiento_id' => 1,
            'area_id' => 5,
        ]);

        $juan = User::create([
            'name'  => 'Juan Carlos Soto',
            'username' => 'juan',
            'role'  => 'Paciente',
            'avatar' => 'default.png',
            'email' => '',
            'password' => '1234567', // o Hash::make('7296413')
            'establecimiento_id' => 1,
            'area_id' => 5,
        ]);
//        usuario admicion
        $admision = User::create([
            'name'  => 'Lucia Fernandez',
            'username' => 'admision',
            'role'  => 'Admision',
            'avatar' => 'default.png',
            'email' => '',
            'password' => '1234567', // o Hash::make('admision123')
            'establecimiento_id' => 1,
            'area_id' => 5,
        ]);
        $permisos = [
            'Dashboard',
            'Usuarios',
            'Pacientes',
            'Doctores',
            'Establecimientos',
            'Servicios',
            'Consentimientos',
            'Solicitudes',
            'Area preanalitica',
            'Analitica',
            'Formularios',
            'Módulo inventario',
            'Modulo compras',
            'Modulo detalle compras',
            'Módulo movimiento',
            'Módulo de faltantes y sobrantes',
            'Reportes',
            'Ver todos los pedidos',
        ];
//        protected $fillable = [
//        'fecha_recepcion', 'hora_recepcion',
//        'nombre_completo', 'fecha_nac', 'genero', 'edad',
//        'ci', 'telefono', 'direccion',
//        'discapacidad', 'discapacidad_cual', 'discapacidad_otro',
//        'embarazo', 'fum', 'sem_gest'
//    ];
        $pacienteAdimer = Paciente::create([
            'nombre_completo' => 'Adimer Paul Chambi Ajata',
            'fecha_nac' => '1989-04-02',
            'genero' => 'M',
            'edad' => 36,
            'ci' => '7336199',
            'telefono' => '69603027',
            'direccion' => 'Av. Siempre Viva 742',
            'fecha_recepcion' => now()->toDateString(),
            'hora_recepcion' => now()->toTimeString(),
        ]);
        foreach ($permisos as $permiso) {
            Permission::create(['name' => $permiso]);
        }
        $userAdmin->givePermissionTo(Permission::all());

//        colcoar todo los servico al hapital 1
        $servicios = \App\Models\Servicio::all();
        foreach ($servicios as $servicio) {
            $servicio->establecimientos()->attach($establecimiento->id);
        }
//        coloca 10 servicos al hospital 2
        $servicios2 = \App\Models\Servicio::inRandomOrder()->take(10)->get();
        foreach ($servicios2 as $servicio) {
            $servicio->establecimientos()->attach($establecimiento2->id);
        }
    }
}
