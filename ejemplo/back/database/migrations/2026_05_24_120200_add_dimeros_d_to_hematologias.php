<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hematologias', function (Blueprint $table) {
            if (! Schema::hasColumn('hematologias', 'dimeros_d')) {
                $table->decimal('dimeros_d', 8, 3)->nullable()->after('fibrinogeno');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hematologias', function (Blueprint $table) {
            if (Schema::hasColumn('hematologias', 'dimeros_d')) {
                $table->dropColumn('dimeros_d');
            }
        });
    }
};
