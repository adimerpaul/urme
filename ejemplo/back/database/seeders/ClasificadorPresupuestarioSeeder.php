<?php

namespace Database\Seeders;

use App\Models\Grupo;
use App\Models\Partida;
use App\Models\Subpartida;
use Illuminate\Database\Seeder;

class ClasificadorPresupuestarioSeeder extends Seeder
{
    public function run(): void
    {
        $grupoServicios = $this->grupo('20000', 'SERVICIOS NO PERSONALES');
        $partidaServicios = $this->partida($grupoServicios, '25000', 'Servicios Profesionales y Comerciales');
        $this->subpartida($partidaServicios, '25600', 'Servicios de Imprenta, Fotocopiado y Fotográficos');

        $grupoMateriales = $this->grupo('30000', 'MATERIALES Y SUMINISTROS');
        $partidaAlimentos = $this->partida($grupoMateriales, '31100', 'Alimentos y Bebidas para Personas, Desayuno Escolar y Otros');
        $this->subpartida($partidaAlimentos, '31110', 'Gastos por Refrigerios al Personal Permanente');
        $this->subpartida($partidaAlimentos, '31140', 'Alimentación Hospitalaria, Penitenciaria, Aeronaves y Otras Específicas');
        $partidaPapel = $this->partida($grupoMateriales, '32000', 'Productos de Papel, Cartón e Impresos');
        $this->subpartida($partidaPapel, '32100', 'Papel');
        $this->subpartida($partidaPapel, '32200', 'Productos de Artes Gráficas');
        $this->subpartida($partidaPapel, '32300', 'Libros, Manuales y Revistas');
        $this->subpartida($partidaPapel, '32400', 'Textos de Enseñanza');
        $this->subpartida($partidaPapel, '32500', 'Periódicos y Boletines');

        $partidaTextiles = $this->partida($grupoMateriales, '33000', 'Textiles y Vestuario');
        $this->subpartida($partidaTextiles, '33100', 'Hilados, Telas, Fibras y Algodón');
        $this->subpartida($partidaTextiles, '33200', 'Confecciones Textiles');
        $this->subpartida($partidaTextiles, '33300', 'Prendas de Vestir');
        $this->subpartida($partidaTextiles, '33400', 'Calzados');

        $partidaCombustibles = $this->partida($grupoMateriales, '34000', 'Combustibles, Productos Químicos, Farmacéuticos y Otras Fuentes de Energía');
        $this->subpartida($partidaCombustibles, '34100', 'Combustibles, Lubricantes, Derivados y otras Fuentes de Energía');
        $this->subpartida($partidaCombustibles, '34110', 'Combustibles, Lubricantes y Derivados para consumo');
        $this->subpartida($partidaCombustibles, '34200', 'Productos Químicos y Farmacéuticos');
        $this->subpartida($partidaCombustibles, '34300', 'Llantas y Neumáticos');
        $this->subpartida($partidaCombustibles, '34400', 'Productos de Cuero y Caucho');
        $this->subpartida($partidaCombustibles, '34500', 'Productos de Minerales no Metálicos y Plásticos');
        $this->subpartida($partidaCombustibles, '34600', 'Productos Metálicos');
        $this->subpartida($partidaCombustibles, '34800', 'Herramientas Menores');

        $partidaVarios = $this->partida($grupoMateriales, '39000', 'Productos Varios');
        $this->subpartida($partidaVarios, '39100', 'Material de Limpieza e Higiene');
        $this->subpartida($partidaVarios, '39200', 'Material Deportivo y Recreativo');
        $this->subpartida($partidaVarios, '39300', 'Utensilios de Cocina y Comedor');
        $this->subpartida($partidaVarios, '39400', 'Instrumental Menor Médico-Quirúrgico');
        $this->subpartida($partidaVarios, '39500', 'Útiles de Escritorio y Oficina');
        $this->subpartida($partidaVarios, '39600', 'Útiles Educacionales, Culturales y de Capacitación');
        $this->subpartida($partidaVarios, '39700', 'Útiles y Materiales Eléctricos');
        $this->subpartida($partidaVarios, '39800', 'Otros Repuestos y Accesorios');
        $this->subpartida($partidaVarios, '39900', 'Otros Materiales y Suministros');
    }

    private function grupo(string $codigo, string $nombre): Grupo
    {
        return Grupo::updateOrCreate(
            ['codigo' => $codigo],
            ['num' => (int) $codigo, 'nombre' => $nombre]
        );
    }

    private function partida(Grupo $grupo, string $codigo, string $nombre): Partida
    {
        return Partida::updateOrCreate(
            ['codigo' => $codigo],
            ['grupo_id' => $grupo->id, 'num' => (int) $codigo, 'nombre' => $nombre]
        );
    }

    private function subpartida(Partida $partida, string $codigo, string $nombre): Subpartida
    {
        return Subpartida::updateOrCreate(
            ['codigo' => $codigo],
            ['partida_id' => $partida->id, 'num' => (int) $codigo, 'nombre' => $nombre]
        );
    }
}
