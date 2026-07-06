<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MaintenanceScheduleController;
use App\Http\Controllers\MaintenanceRecordController;
use App\Http\Controllers\MachineComponentController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes (unprotected)
Route::post('/login', [AuthController::class, 'login']);

// All other routes require authentication
Route::middleware('auth:sanctum')->group(function () {
    // Auth profile & logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'profile']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // User management
    Route::get('/users', [MachineController::class, 'getUsers']);
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{id}/role', [UserController::class, 'updateRole']);
    Route::put('/admin/users/{id}/city', [UserController::class, 'updateCity']);
    Route::put('/admin/users/{id}/password', [UserController::class, 'updatePassword']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
    Route::get('/admin/logs', [UserController::class, 'activityLogs']);

    // Machine Routes
    Route::get('/machines', [MachineController::class, 'index']);
    Route::post('/machines', [MachineController::class, 'store']);
    Route::post('/machines/import', [MachineController::class, 'bulkStore']);
    Route::get('/machines/check-kode', [MachineController::class, 'checkKode']);
    Route::get('/machines/{id}', [MachineController::class, 'show']);
    Route::put('/machines/{id}', [MachineController::class, 'update']);
    Route::delete('/machines/{id}', [MachineController::class, 'destroy']);

    // Machine Components (nested under machine)
    Route::get('/machines/{machineId}/components', [MachineComponentController::class, 'index']);
    Route::post('/machines/{machineId}/components', [MachineComponentController::class, 'store']);
    Route::post('/machines/{machineId}/components/import', [MachineComponentController::class, 'bulkStore']);

    // Machine Components (standalone)
    Route::post('/components/import-global', [MachineComponentController::class, 'bulkStoreGlobal']);
    Route::put('/components/{id}', [MachineComponentController::class, 'update']);
    Route::delete('/components/{id}', [MachineComponentController::class, 'destroy']);
    Route::get('/components/{id}/history', [MachineComponentController::class, 'history']);

    // Maintenance Schedules
    Route::get('/schedules/notifications', [MaintenanceScheduleController::class, 'notifications']);
    Route::get('/schedules', [MaintenanceScheduleController::class, 'index']);
    Route::post('/schedules', [MaintenanceScheduleController::class, 'store']);
    Route::get('/schedules/{id}', [MaintenanceScheduleController::class, 'show']);
    Route::put('/schedules/{id}', [MaintenanceScheduleController::class, 'update']);
    Route::delete('/schedules/{id}', [MaintenanceScheduleController::class, 'destroy']);

    // Maintenance Records
    Route::get('/records', [MaintenanceRecordController::class, 'index']);
    Route::post('/records', [MaintenanceRecordController::class, 'store']);
    Route::get('/records/{id}', [MaintenanceRecordController::class, 'show']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/bulk', [RoleController::class, 'bulkUpdate']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::post('/approvals/{recordId}/decide', [ApprovalController::class, 'decide']);
    Route::get('/approval-flow', [ApprovalController::class, 'flowConfig']);
    Route::put('/approval-flow', [ApprovalController::class, 'updateFlowConfig']);

    // Holiday proxy — fetches Indonesian national holidays server-side (avoids browser CORS)
    Route::get('/holidays', function (Request $request) {
        $year = (int) $request->query('year', date('Y'));
        $cacheKey = "holidays_{$year}";
        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(24), function () use ($year) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->get("https://api-hari-libur.vercel.app/api?year={$year}");
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Holiday API fetch failed for {$year}: " . $e->getMessage());
            }
            return [];
        });
        return response()->json(['data' => $data]);
    });
});
