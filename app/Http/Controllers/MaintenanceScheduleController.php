<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSchedule;
use App\Models\ScheduleOccurrence;
use App\Services\ScheduleOccurrenceGenerator;
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
        $tomorrow = Carbon::tomorrow();
        $monthStart = Carbon::today()->startOfMonth();
        $monthEnd = Carbon::today()->endOfMonth();

        // Base query: occurrences due within H-1..tomorrow OR occurrences further ahead
        // that have no approved record yet for that period in the current month.
        $occurrences = ScheduleOccurrence::with(['schedule', 'machine.records.latestApproval', 'machine.components'])
            ->whereHas('schedule', fn ($q) => $q->where('is_active', true))
            ->where(function ($query) use ($tomorrow, $monthStart, $monthEnd) {
                $query->where('due_date', '<=', $tomorrow)
                    ->orWhere(function ($q) use ($monthStart, $monthEnd) {
                        $q->where('due_date', '>', Carbon::tomorrow())
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

        // Shape the response like the schedules this endpoint used to return (machine_id,
        // next_due_date, schedule_type, id) so existing frontend consumers keep working.
        $result = $occurrences->map(function (ScheduleOccurrence $occ) {
            return [
                'id' => $occ->schedule_id,
                'machine_id' => $occ->machine_id,
                'schedule_type' => $occ->schedule?->schedule_type,
                'next_due_date' => $occ->due_date,
                'machine' => $occ->machine,
            ];
        })->values();

        return response()->json($result);
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
        app(ScheduleOccurrenceGenerator::class)->regenerateForSchedule($schedule);
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
        app(ScheduleOccurrenceGenerator::class)->regenerateForSchedule($schedule);
        return response()->json($schedule);
    }

    public function destroy($id)
    {
        $schedule = MaintenanceSchedule::findOrFail($id);
        $schedule->delete();
        return response()->json(null, 204);
    }
}
