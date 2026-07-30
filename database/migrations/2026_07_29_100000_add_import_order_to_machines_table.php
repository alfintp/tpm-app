<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->unsignedInteger('import_order')->nullable()->after('kota');
        });

        // Backfill import_order for existing machines using their current creation order,
        // scoped per city (sby / pasuruan), so the row-order-based schedule assignment
        // works immediately without requiring a re-import.
        $machines = DB::table('machines')->orderBy('kota')->orderBy('created_at')->orderBy('id')->get(['id', 'kota']);
        $counters = [];
        foreach ($machines as $machine) {
            $kota = $machine->kota ?? 'lainnya';
            $counters[$kota] = ($counters[$kota] ?? 0) + 1;
            DB::table('machines')->where('id', $machine->id)->update(['import_order' => $counters[$kota]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machines', function (Blueprint $table) {
            $table->dropColumn('import_order');
        });
    }
};
