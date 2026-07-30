<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->foreignUuid('unlock_requested_by_id')
                  ->nullable()
                  ->after('unlock_requested_period')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'unlock_requested_by_id');
            $table->dropColumn('unlock_requested_by_id');
        });
    }
};
