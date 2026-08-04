<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->foreignUuid('unlock_approved_by_id')
                  ->nullable()
                  ->after('unlock_requested_by_id')
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('unlock_approved_at')->nullable()->after('unlock_approved_by_id');
            $table->text('unlock_approval_notes')->nullable()->after('unlock_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\User::class, 'unlock_approved_by_id');
            $table->dropColumn(['unlock_approved_by_id', 'unlock_approved_at', 'unlock_approval_notes']);
        });
    }
};
