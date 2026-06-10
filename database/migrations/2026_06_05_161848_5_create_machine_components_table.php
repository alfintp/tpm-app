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
        Schema::create('machine_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('machine_id')->constrained('machines')->onDelete('cascade');
            $table->string('category'); // e.g. Mechanical, Technical
            $table->string('name'); // e.g. Mold Die Set
            $table->string('specification')->nullable(); // e.g. Titanium Coated
            $table->integer('qty')->default(1);
            $table->string('unit')->default('pcs'); // e.g. pcs, set, meter
            $table->date('last_replaced_at')->nullable();
            $table->float('last_condition_pct')->nullable();
            $table->string('maintenance_schedule')->nullable(); // e.g. W1
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_components');
    }
};
