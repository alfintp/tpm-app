<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_actions', function (Blueprint $table) {
            $table->foreignUuid('stock_id')->nullable()->after('machine_component_id')->constrained('stocks')->onDelete('set null');
            $table->integer('stock_qty_used')->nullable()->after('stock_id');
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_actions', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->dropColumn(['stock_id', 'stock_qty_used']);
        });
    }
};
