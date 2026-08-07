<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->foreignUuid('maintenance_action_id')->nullable()->constrained('maintenance_actions')->onDelete('set null');
            $table->foreignUuid('maintenance_record_id')->constrained('maintenance_records')->onDelete('cascade');
            $table->foreignUuid('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignUuid('machine_component_id')->nullable()->constrained('machine_components')->onDelete('set null');
            $table->integer('quantity_used');
            $table->date('used_at');
            $table->foreignUuid('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_usages');
    }
};
