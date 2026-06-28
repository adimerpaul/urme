<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE establecimientos MODIFY COLUMN tipo ENUM('PUBLICO','PRIVADO','URBANO','RURAL') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE establecimientos MODIFY COLUMN tipo ENUM('PUBLICO','PRIVADO') NULL");
    }
};
