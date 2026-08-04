<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_approve_unlock')->default(false)->after('is_manager');
        });

        // Grant unlock approval to admin and manager by default
        DB::table('roles')->whereIn('name', ['admin', 'manager'])->update(['can_approve_unlock' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('can_approve_unlock');
        });
    }
};
