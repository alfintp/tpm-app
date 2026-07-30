<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_window_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Berapa hari sebelum jadwal (H-x) mesin sudah bisa dilaporkan.
            $table->unsignedTinyInteger('days_before')->default(2);
            // Berapa hari setelah jadwal (H+y) laporan masih dianggap tepat waktu.
            $table->unsignedTinyInteger('days_after')->default(0);
            $table->timestamps();
        });

        // Seed satu baris default (singleton config)
        \App\Models\MaintenanceWindowSetting::create([
            'days_before' => 2,
            'days_after' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_window_settings');
    }
};
