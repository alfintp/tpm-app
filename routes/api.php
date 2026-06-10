<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\MaintenanceRecordController;
use App\Http\Controllers\Api\MachineComponentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Machine Routes
Route::get('/machines', [MachineController::class, 'index']);
Route::post('/machines', [MachineController::class, 'store']);
Route::get('/machines/{id}', [MachineController::class, 'show']);
Route::put('/machines/{id}', [MachineController::class, 'update']);
Route::delete('/machines/{id}', [MachineController::class, 'destroy']);
Route::get('/users', [MachineController::class, 'getUsers']);

// Machine Components (nested under machine)
Route::get('/machines/{machineId}/components', [MachineComponentController::class, 'index']);
Route::post('/machines/{machineId}/components', [MachineComponentController::class, 'store']);

// Machine Components (standalone - for edit/delete/history)
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

// Helper route - gets the first user (dummy auth)
Route::get('/dummy-user', function () {
    return response()->json(\App\Models\User::first() ?? ['id' => null]);
});
