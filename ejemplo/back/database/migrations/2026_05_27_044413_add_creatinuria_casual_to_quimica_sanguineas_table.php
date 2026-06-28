<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->decimal('creatinuria_casual', 10, 2)->nullable()->after('creatinuria_24h');
        });
    }

    public function down(): void
    {
        Schema::table('quimica_sanguineas', function (Blueprint $table) {
            $table->dropColumn('creatinuria_casual');
        });
    }
};
