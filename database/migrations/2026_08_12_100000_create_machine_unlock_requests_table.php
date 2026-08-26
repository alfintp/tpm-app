<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_unlock_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->foreignUuid('requested_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->string('requested_period')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignUuid('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_unlock_requests');
    }
};
