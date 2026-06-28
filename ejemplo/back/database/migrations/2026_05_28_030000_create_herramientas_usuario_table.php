<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('herramientas_usuario', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('valor')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Permission::firstOrCreate(['name' => 'Tiempo creación de usuario', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Schema::dropIfExists('herramientas_usuario');
        Permission::where('name', 'Tiempo creación de usuario')->delete();
    }
};
