<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel's enum modifier does not support altering enum values portably,
        // so use a raw statement to recreate the column with the new allowed roles.
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'technician', 'manager', 'karo', 'qc', 'wpv') DEFAULT 'technician'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'technician', 'manager') DEFAULT 'technician'");
    }
};
