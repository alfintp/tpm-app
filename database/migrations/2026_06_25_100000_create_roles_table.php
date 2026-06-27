<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            $table->string('display_name', 100);
            $table->boolean('can_approve')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $roles = [
            ['id' => Str::uuid()->toString(), 'name' => 'admin', 'display_name' => 'Administrator', 'can_approve' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'technician', 'display_name' => 'Teknisi', 'can_approve' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'manager', 'display_name' => 'Manager', 'can_approve' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'karo', 'display_name' => 'Karo', 'can_approve' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'qc', 'display_name' => 'QC', 'can_approve' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => Str::uuid()->toString(), 'name' => 'wpv', 'display_name' => 'WPV', 'can_approve' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('roles')->insert($roles);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
