<?php

namespace App\Http\Controllers\Api;

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
        // Find schedules where next_due_date is tomorrow, today, or in the past (overdue)
        $tomorrow = Carbon::tomorrow();
        
        $schedules = MaintenanceSchedule::with('machine')
            ->where('is_active', true)
            ->where('next_due_date', '<=', $tomorrow)
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
