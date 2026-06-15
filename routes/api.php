<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\MaintenanceRecordController;
use App\Http\Controllers\Api\MachineComponentController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes (unprotected)
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth profile & logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user/profile', [AuthController::class, 'profile']);
    
    // User management (admin & manager only inside controllers)
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{id}/role', [UserController::class, 'updateRole']);
    Route::put('/admin/users/{id}/city', [UserController::class, 'updateCity']);
    Route::put('/admin/users/{id}/password', [UserController::class, 'updatePassword']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
    Route::get('/admin/logs', [UserController::class, 'activityLogs']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Machine Routes
Route::get('/machines', [MachineController::class, 'index'])->middleware('auth:sanctum');
Route::post('/machines', [MachineController::class, 'store']);
Route::post('/machines/import', [MachineController::class, 'bulkStore'])->middleware('auth:sanctum');
Route::get('/machines/{id}', [MachineController::class, 'show'])->middleware('auth:sanctum');
Route::put('/machines/{id}', [MachineController::class, 'update']);
Route::delete('/machines/{id}', [MachineController::class, 'destroy']);
Route::get('/users', [MachineController::class, 'getUsers']);
Route::get('/machines/check-kode', [MachineController::class, 'checkKode']);

// Machine Components (nested under machine)
Route::get('/machines/{machineId}/components', [MachineComponentController::class, 'index']);
Route::post('/machines/{machineId}/components', [MachineComponentController::class, 'store']);
Route::post('/machines/{machineId}/components/import', [MachineComponentController::class, 'bulkStore'])->middleware('auth:sanctum');

// Machine Components (standalone - for edit/delete/history)
Route::post('/components/import-global', [MachineComponentController::class, 'bulkStoreGlobal'])->middleware('auth:sanctum');
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
Route::post('/records', [MaintenanceRecordController::class, 'store'])->middleware('auth:sanctum');
Route::get('/records/{id}', [MaintenanceRecordController::class, 'show']);

// Approvals
Route::get('/approvals', [ApprovalController::class, 'index'])->middleware('auth:sanctum');
Route::post('/approvals/{recordId}/decide', [ApprovalController::class, 'decide'])->middleware('auth:sanctum');

// Helper route - gets the logged in user or first user (dummy auth)
Route::get('/dummy-user', function (Illuminate\Http\Request $request) {
    if (auth('sanctum')->check()) {
        return response()->json(auth('sanctum')->user());
    }
    return response()->json(\App\Models\User::first() ?? ['id' => null]);
});
