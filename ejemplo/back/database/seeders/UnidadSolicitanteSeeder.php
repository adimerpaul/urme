<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadSolicitanteSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'CIRUGIA MUJERES',
            'CIRUGIA VARONES',
            'CIRUGIA GENERAL',
            'CONSULTA EXTERNA ANESTESIOLOGIA',
            'CONSULTA EXTERNA CARDIOLOGIA',
            'CONSULTA EXTERNA CIRUGIA GENERAL',
            'CONSULTA EXTERNA CIRUGIA MAXILO FACIAL',
            'CONSULTA EXTERNA DERMATOLOGIA',
            'CONSULTA EXTERNA ENDOCRINOLOGIA',
            'CONSULTA EXTERNA GASTROENTEROLOGIA',
            'CONSULTA EXTERNA GERIATRIA',
            'CONSULTA EXTERNA GINECOLOGIA',
            'CONSULTA EXTERNA HEMATOLOGIA',
            'CONSULTA EXTERNA MEDICINA INTERNA',
            'CONSULTA EXTERNA NEFROLOGIA',
            'CONSULTA EXTERNA NEUMOLOGIA',
            'CONSULTA EXTERNA NEUROCIRUGIA',
            'CONSULTA EXTERNA NEUROLOGIA',
            'CONSULTA EXTERNA NUTRICION',
            'CONSULTA EXTERNA OBSTETRICIA',
            'CONSULTA EXTERNA OFTALMOLOGIA',
            'CONSULTA EXTERNA ONCOLOGIA',
            'CONSULTA EXTERNA OTORRINOLARINGOLOGIA',
            'CONSULTA EXTERNA PEDIATRIA',
            'CONSULTA EXTERNA PSICOLOGIA',
            'CONSULTA EXTERNA PSIQUIATRIA',
            'CONSULTA EXTERNA REUMATOLOGIA',
            'CONSULTA EXTERNA TRAUMATOLOGIA',
            'CONSULTA EXTERNA UROLOGIA',
            'EMERGENCIA EMERGENCIA',
            'HOSPITALIZACION CIRUJIA GENERAL',
            'HOSPITALIZACION GINECOLOGIA',
            'HOSPITALIZACION MEDICINA INTERNA',
            'HOSPITALIZACION NEONATOLOGIA',
            'HOSPITALIZACION OBSTETRICIA',
            'HOSPITALIZACION ONCOLOGIA',
            'HOSPITALIZACION PEDIATRIA',
            'HOSPITALIZACION QUEMADOS',
            'HOSPITALIZACION UNIDAD DE CUIDADOS INTENSIVOS (UCI)',
            'HOSPITALIZACION UNIDAD DE TERAPIA INTENSIVA (UTI)',
            'UNIDAD DE TERAPIA INTENSIVA (UTI)',
            'HOSPITALIZACION UNIDAD DE TERAPIA INTERMEDIA (UTIN)',
            'UNIDAD DE TERAPIA INTERMEDIA (UTIN)',
            'SOLICITUD EXTERNA',
            'OTROS',
        ];

        foreach ($items as $nombre) {
            DB::table('unidad_solicitantes')->updateOrInsert(
                ['nombre' => trim($nombre)],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
