<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('machine_component_id')->constrained('machine_components')->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_indicators');
    }
};
