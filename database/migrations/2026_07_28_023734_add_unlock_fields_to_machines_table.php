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
            $table->enum('unlock_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('unlock_reason');
            $table->timestamp('unlock_expires_at')->nullable()->after('unlock_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn(['unlock_status', 'unlock_expires_at']);
        });
    }
};
