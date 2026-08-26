<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing unlock request data from machines table to machine_unlock_requests
        $machines = DB::table('machines')
            ->whereNotNull('last_unlock_request_at')
            ->whereNotIn('unlock_status', ['none'])
            ->get();

        foreach ($machines as $machine) {
            // Skip if a record already exists for this machine with the same status
            $exists = DB::table('machine_unlock_requests')
                ->where('machine_id', $machine->id)
                ->where('status', $machine->unlock_status)
                ->where('reason', $machine->unlock_reason)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('machine_unlock_requests')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'machine_id' => $machine->id,
                'requested_by_id' => $machine->unlock_requested_by_id,
                'reason' => $machine->unlock_reason ?? '',
                'requested_period' => $machine->unlock_requested_period,
                'status' => $machine->unlock_status,
                'approved_by_id' => $machine->unlock_approved_by_id,
                'approved_at' => $machine->unlock_approved_at,
                'approval_notes' => $machine->unlock_approval_notes,
                'expires_at' => $machine->unlock_expires_at,
                'created_at' => $machine->last_unlock_request_at,
                'updated_at' => $machine->unlock_approved_at ?? $machine->last_unlock_request_at,
            ]);
        }
    }

    public function down(): void
    {
        // No down migration — we don't want to delete migrated data
    }
};
