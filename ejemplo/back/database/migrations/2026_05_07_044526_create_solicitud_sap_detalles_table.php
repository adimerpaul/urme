<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitud_sap_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_sap_id')->constrained('solicitudes_sap')->cascadeOnDelete();
            $table->unsignedSmallInteger('item')->default(1);
            $table->string('part_presup')->nullable();
            $table->string('descripcion');
            $table->string('unidad', 100)->nullable();
            $table->decimal('cantidad', 14, 2)->default(0);
            $table->decimal('precio_unitario', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud_sap_detalles');
    }
};
