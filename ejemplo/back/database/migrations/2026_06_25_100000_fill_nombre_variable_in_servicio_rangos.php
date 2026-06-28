<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('servicio_rangos as sr')
            ->join('area_rangos as ar', 'ar.id', '=', 'sr.area_rango_id')
            ->where(function ($q) {
                $q->whereNull('sr.nombre_variable')
                  ->orWhere('sr.nombre_variable', '');
            })
            ->whereNull('ar.deleted_at')
            ->orderBy('sr.servicio_id')
            ->orderBy('sr.id')
            ->select(
                'sr.id',
                'sr.servicio_id',
                DB::raw('COALESCE(NULLIF(ar.rango_nombre, ""), ar.analito, "") as fuente')
            )
            ->get();

        // Agrupar por servicio para detectar y resolver duplicados
        $porServicio = [];
        foreach ($rows as $row) {
            $porServicio[$row->servicio_id][] = $row;
        }

        foreach ($porServicio as $filas) {
            $usados = [];
            foreach ($filas as $row) {
                $base = $this->toVariable($row->fuente);
                if ($base === '') {
                    continue;
                }

                // Si hay duplicado dentro del mismo servicio, agregar sufijo numérico
                $variable = $base;
                $contador = 2;
                while (in_array($variable, $usados, true)) {
                    $variable = $base . '_' . $contador;
                    $contador++;
                }
                $usados[] = $variable;

                DB::table('servicio_rangos')
                    ->where('id', $row->id)
                    ->update(['nombre_variable' => $variable]);
            }
        }
    }

    public function down(): void
    {
        DB::table('servicio_rangos')->update(['nombre_variable' => null]);
    }

    private function toVariable(string $nombre): string
    {
        $s = mb_strtolower(trim($nombre), 'UTF-8');

        // Reemplazar caracteres acentuados manualmente (sin depender de iconv)
        $from = ['á','é','í','ó','ú','ü','ñ','à','è','ì','ò','ù','â','ê','î','ô','û'];
        $to   = ['a','e','i','o','u','u','n','a','e','i','o','u','a','e','i','o','u'];
        $s = str_replace($from, $to, $s);

        // Reemplazar cualquier carácter que no sea letra o dígito por guión bajo
        $s = preg_replace('/[^a-z0-9]+/', '_', $s);

        // Quitar guiones bajos al inicio/final
        return trim($s, '_');
    }
};
