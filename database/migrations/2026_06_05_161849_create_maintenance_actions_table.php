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
        Schema::create('maintenance_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('record_id')->constrained('maintenance_records')->onDelete('cascade');
            $table->enum('action_type', ['repair', 'replace', 'inspect', 'clean', 'lubricate'])->default('inspect');
            $table->foreignUuid('machine_component_id')->constrained('machine_components')->onDelete('cascade');
            $table->float('condition_before_pct')->nullable();
            $table->float('condition_after_pct')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_actions');
    }
};
