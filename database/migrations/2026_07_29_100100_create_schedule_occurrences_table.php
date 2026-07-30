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
        Schema::create('schedule_occurrences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_id')->constrained('maintenance_schedules')->onDelete('cascade');
            $table->foreignUuid('machine_id')->constrained('machines')->onDelete('cascade');
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->date('due_date');
            $table->date('original_date');
            $table->boolean('is_shifted')->default(false);
            $table->timestamps();

            $table->index(['machine_id', 'due_date']);
            $table->index(['schedule_id', 'period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_occurrences');
    }
};
