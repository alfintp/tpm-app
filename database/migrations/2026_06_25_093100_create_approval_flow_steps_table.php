<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flow_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('role', ['karo', 'qc', 'wpv', 'manager']);
            $table->unsignedTinyInteger('step_order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('step_order');
        });

        // Seed default 4-step approval flow: Karo > QC > WPV > Manager
        $steps = [
            ['role' => 'karo', 'step_order' => 1],
            ['role' => 'qc', 'step_order' => 2],
            ['role' => 'wpv', 'step_order' => 3],
            ['role' => 'manager', 'step_order' => 4],
        ];
        foreach ($steps as $step) {
            \App\Models\ApprovalFlowStep::create($step + ['is_active' => true]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_steps');
    }
};
