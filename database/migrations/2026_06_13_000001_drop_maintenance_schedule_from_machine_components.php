<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_components', function (Blueprint $table) {
            if (Schema::hasColumn('machine_components', 'maintenance_schedule')) {
                $table->dropColumn('maintenance_schedule');
            }
        });
    }

    public function down(): void
    {
        Schema::table('machine_components', function (Blueprint $table) {
            $table->string('maintenance_schedule')->nullable()->after('last_condition_pct');
        });
    }
};
