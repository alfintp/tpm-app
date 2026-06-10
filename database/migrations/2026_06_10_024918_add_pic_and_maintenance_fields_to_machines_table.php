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
        Schema::table('machines', function (Blueprint $table) {
            $table->uuid('pic_mesin_id')->nullable()->after('status');
            $table->foreign('pic_mesin_id')->references('id')->on('users')->onDelete('set null');
            $table->integer('maintenance_duration')->nullable()->after('pic_mesin_id')->comment('Duration in days');
            $table->date('maintenance_start_date')->nullable()->after('maintenance_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropForeign(['pic_mesin_id']);
            $table->dropColumn(['pic_mesin_id', 'maintenance_duration', 'maintenance_start_date']);
        });
    }
};
