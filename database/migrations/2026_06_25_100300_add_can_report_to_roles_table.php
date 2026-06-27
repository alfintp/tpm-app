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
            $table->boolean('can_report')->default(false)->after('can_approve');
        });

        // Grant report permission to admin and technician roles by default
        DB::table('roles')->whereIn('name', ['admin', 'technician'])->update(['can_report' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('can_report');
        });
    }
};
