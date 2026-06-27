<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('machine_components')
            ->where('difficulty', 'mudah')
            ->update(['difficulty' => 'ringan']);
    }

    public function down(): void
    {
        DB::table('machine_components')
            ->where('difficulty', 'ringan')
            ->update(['difficulty' => 'mudah']);
    }
};
