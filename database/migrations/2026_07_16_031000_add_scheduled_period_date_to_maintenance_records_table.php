<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->date('scheduled_period_date')->nullable()->after('schedule_id');
            $table->index(['machine_id', 'schedule_id', 'scheduled_period_date'], 'maintenance_records_period_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropIndex('maintenance_records_period_lookup');
            $table->dropColumn('scheduled_period_date');
        });
    }
};
