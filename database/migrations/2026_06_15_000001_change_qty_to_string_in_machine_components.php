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
        Schema::table('machine_components', function (Blueprint $table) {
            // Change qty from integer to nullable string
            $table->string('qty', 50)->nullable()->change();
            // Make category nullable
            $table->string('category', 100)->nullable()->change();
            // Make unit nullable
            $table->string('unit', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_components', function (Blueprint $table) {
            // Revert changes
            $table->integer('qty')->default(1)->change();
            $table->string('category', 100)->nullable(false)->change();
            $table->string('unit', 50)->nullable(false)->change();
        });
    }
};
