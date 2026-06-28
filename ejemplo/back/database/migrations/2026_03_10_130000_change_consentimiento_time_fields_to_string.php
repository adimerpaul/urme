<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
//        DB::statement("ALTER TABLE consentimientos MODIFY hora_recepcion VARCHAR(20) NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_orina VARCHAR(20) NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_liquidos VARCHAR(20) NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_esputo VARCHAR(20) NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_secreciones VARCHAR(20) NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_heces VARCHAR(20) NULL");
    }

    public function down(): void
    {
//        DB::statement("ALTER TABLE consentimientos MODIFY hora_recepcion TIME NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_orina TIME NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_liquidos TIME NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_esputo TIME NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_secreciones TIME NULL");
//        DB::statement("ALTER TABLE consentimientos MODIFY hr_recoleccion_heces TIME NULL");
    }
};
