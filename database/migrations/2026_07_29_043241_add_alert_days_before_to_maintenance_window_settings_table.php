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
        Schema::table('maintenance_window_settings', function (Blueprint $table) {
            $table->integer('alert_days_before')->default(7)->after('days_before');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_window_settings', function (Blueprint $table) {
            $table->dropColumn('alert_days_before');
        });
    }
};
