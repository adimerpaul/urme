<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $table->unsignedBigInteger('servicio_id')->nullable();
            $table->foreign('servicio_id')->references('id')->on('servicios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        $now = now();
        $equipos = [
            'EASIS LYTE PLUS', 'A25', 'EDAN', 'EASIS',
            'Mindray BS-240 Pro', 'Star fax 4500',
            'GasometroRamiometer ABL800',
            'Lector de electrolitos Easy Lyte Plus',
            'Nefelometro Nephstar', 'Gasómetro Edan i15', 'Otros',
        ];
        foreach ($equipos as $nombre) {
            DB::table('equipos')->insert([
                'nombre'      => $nombre,
                'estado'      => 'ACTIVO',
                'servicio_id' => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
