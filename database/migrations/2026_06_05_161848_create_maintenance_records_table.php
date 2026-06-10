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
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignUuid('technician_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('schedule_id')->nullable()->constrained('maintenance_schedules')->onDelete('set null');
            $table->date('maintenance_date');
            $table->float('condition_before_pct')->nullable();
            $table->float('condition_after_pct')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'cancelled'])->default('planned');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
