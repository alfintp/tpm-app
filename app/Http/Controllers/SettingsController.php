<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceWindowSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function getMaintenanceWindow()
    {
        $setting = MaintenanceWindowSetting::current();

        return response()->json([
            'days_before' => $setting->days_before,
            'days_after' => $setting->days_after,
            'alert_days_before' => $setting->alert_days_before,
        ]);
    }

    public function updateMaintenanceWindow(Request $request)
    {
        $authUser = $request->user();
        if (!$authUser || $authUser->role !== 'admin') {
            return response()->json(['message' => 'Hanya admin yang dapat mengatur jadwal maintenance.'], 403);
        }

        $validated = $request->validate([
            'days_before' => 'required|integer|min:0|max:30',
            'days_after' => 'required|integer|min:0|max:30',
            'alert_days_before' => 'required|integer|min:0|max:30',
        ]);

        $setting = MaintenanceWindowSetting::current();
        $setting->update($validated);

        return response()->json([
            'days_before' => $setting->days_before,
            'days_after' => $setting->days_after,
            'alert_days_before' => $setting->alert_days_before,
        ]);
    }
}
