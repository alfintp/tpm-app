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
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\NotificationController;

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
    Route::get('/machines/unlock-history', [MachineController::class, 'unlockHistory']);
    Route::get('/machines/my-unlock-requests', [MachineController::class, 'myUnlockRequests']);
    Route::post('/machines/{id}/request-unlock', [MachineController::class, 'requestUnlock']);
    Route::post('/machines/{id}/approve-unlock', [MachineController::class, 'approveUnlock']);
    Route::get('/machines/{id}', [MachineController::class, 'show']);
    Route::put('/machines/{id}', [MachineController::class, 'update']);
    Route::delete('/machines/{id}', [MachineController::class, 'destroy']);

    // Machine Components (nested under machine)
    Route::get('/machines/{machineId}/components', [MachineComponentController::class, 'index']);
    Route::post('/machines/{machineId}/components', [MachineComponentController::class, 'store']);
    Route::post('/machines/{machineId}/components/import', [MachineComponentController::class, 'bulkStore']);

    // Machine Components (standalone)
    Route::get('/components', [MachineComponentController::class, 'allComponents']);
    Route::post('/components/import-global', [MachineComponentController::class, 'bulkStoreGlobal']);
    Route::post('/components/import-indicators', [MachineComponentController::class, 'bulkImportIndicators']);
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
    Route::get('/approvals/{recordId}', [ApprovalController::class, 'show']);
    Route::post('/approvals/{recordId}/decide', [ApprovalController::class, 'decide']);
    Route::get('/approval-flow', [ApprovalController::class, 'flowConfig']);
    Route::put('/approval-flow', [ApprovalController::class, 'updateFlowConfig']);

    // Maintenance report window settings (admin-configurable H-x to H+y)
    Route::get('/settings/maintenance-window', [SettingsController::class, 'getMaintenanceWindow']);
    Route::put('/settings/maintenance-window', [SettingsController::class, 'updateMaintenanceWindow']);

    // Stock Routes (static paths before {id} wildcard)
    Route::get('/stocks', [StockController::class, 'index']);
    Route::get('/stocks/low', [StockController::class, 'lowStock']);
    Route::get('/stocks/usages', [StockController::class, 'usages']);
    Route::post('/stocks', [StockController::class, 'store']);
    Route::post('/stocks/import', [StockController::class, 'import']);
    Route::put('/stocks/bulk-limit', [StockController::class, 'bulkLimit']);
    Route::put('/stocks/{id}', [StockController::class, 'update']);
    Route::delete('/stocks/{id}', [StockController::class, 'destroy']);
    Route::post('/stocks/{id}/restock', [StockController::class, 'restock']);
    Route::get('/stocks/{id}/usages', [StockController::class, 'usageHistory']);

    // Notification Routes (user-facing)
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/dynamic', [NotificationController::class, 'dynamic']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Notification Admin Routes (admin only)
    Route::get('/notifications/admin/list', [NotificationController::class, 'list']);
    Route::post('/notifications/admin', [NotificationController::class, 'store']);
    Route::put('/notifications/admin/{id}', [NotificationController::class, 'update']);
    Route::delete('/notifications/admin/{id}', [NotificationController::class, 'destroy']);

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
