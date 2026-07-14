<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MaintenanceScheduleController extends Controller
{
    public function index()
    {
        $schedules = MaintenanceSchedule::with('machine')->get();
        return response()->json($schedules);
    }
    
    public function notifications()
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        // Base query: schedules with next_due <= tomorrow OR schedules that were advanced
        // but have no approved record in the current month
        $schedules = MaintenanceSchedule::with(['machine.records.latestApproval', 'machine.components'])
            ->where('is_active', true)
            ->where(function ($query) use ($tomorrow, $monthStart, $monthEnd) {
                // Case 1: next_due is within H-1..tomorrow
                $query->where('next_due_date', '<=', $tomorrow)
                    // OR Case 2: next_due is in the future (already advanced) but no approved
                    // record exists in the current month for this machine
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->where('next_due_date', '>', Carbon::tomorrow())
                            ->whereDoesntHave('machine.records', function ($recordQ) use ($monthStart, $monthEnd) {
                                $recordQ->where('status', 'completed')
                                    ->whereHas('latestApproval', function ($approvalQ) {
                                        $approvalQ->where('decision', 'approved');
                                    })
                                    ->whereBetween('maintenance_date', [$monthStart, $monthEnd]);
                            });
                    });
            })
            ->get();

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'interval_days' => 'required|integer|min:1',
            'schedule_type' => 'required|in:preventive,predictive,breakdown',
            'next_due_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $schedule = MaintenanceSchedule::create($validated);
        return response()->json($schedule, 201);
    }

    public function show($id)
    {
        $schedule = MaintenanceSchedule::with('machine')->findOrFail($id);
        return response()->json($schedule);
    }

    public function update(Request $request, $id)
    {
        $schedule = MaintenanceSchedule::findOrFail($id);

        $validated = $request->validate([
            'machine_id' => 'sometimes|required|exists:machines,id',
            'interval_days' => 'sometimes|required|integer|min:1',
            'schedule_type' => 'sometimes|required|in:preventive,predictive,breakdown',
            'next_due_date' => 'sometimes|required|date',
            'is_active' => 'boolean',
        ]);

        $schedule->update($validated);
        return response()->json($schedule);
    }

    public function destroy($id)
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->delete();
        return response()->json(null, 204);
    }
}
