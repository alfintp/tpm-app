<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_flow_steps', function (Blueprint $table) {
            // Add reporter_role with a default of 'technician' (since all existing ones are from technicians)
            $table->string('reporter_role', 50)->default('technician')->after('id');
            
            // Drop unique constraint on step_order
            try {
                $table->dropUnique(['step_order']);
            } catch (\Exception $e) {
                // SQLite or other DBs might name it differently, or unique constraint is already dropped
            }
        });

        Schema::table('approval_flow_steps', function (Blueprint $table) {
            // Re-add a composite unique constraint on reporter_role and step_order
            $table->unique(['reporter_role', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::table('approval_flow_steps', function (Blueprint $table) {
            try {
                $table->dropUnique(['reporter_role', 'step_order']);
            } catch (\Exception $e) {}
            
            $table->dropColumn('reporter_role');
        });

        Schema::table('approval_flow_steps', function (Blueprint $table) {
            try {
                $table->unique('step_order');
            } catch (\Exception $e) {}
        });
    }
};
