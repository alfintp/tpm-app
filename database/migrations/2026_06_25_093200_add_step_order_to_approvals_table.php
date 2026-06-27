<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->unsignedTinyInteger('step_order')->default(1)->after('record_id');
            $table->index(['record_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropIndex(['record_id', 'step_order']);
            $table->dropColumn('step_order');
        });
    }
};
