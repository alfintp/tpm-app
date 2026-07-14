<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_action_indicators', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('maintenance_action_id')->constrained('maintenance_actions')->onDelete('cascade');
            $table->foreignUuid('component_indicator_id')->constrained('component_indicators')->onDelete('cascade');
            $table->boolean('value')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_action_indicators');
    }
};
