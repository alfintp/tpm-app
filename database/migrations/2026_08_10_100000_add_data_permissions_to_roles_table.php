<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_add_data')->default(false)->after('can_approve_unlock');
            $table->boolean('can_delete_data')->default(false)->after('can_add_data');
        });

        // Grant add/delete data permissions to admin by default
        DB::table('roles')->where('name', 'admin')->update([
            'can_add_data' => true,
            'can_delete_data' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['can_add_data', 'can_delete_data']);
        });
    }
};
