<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\ActivityLog;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Halaman Login menggunakan Inertia
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

// Halaman Dashboard menggunakan Inertia
Route::get('/', function () {
    $tomorrow = \Carbon\Carbon::tomorrow();

    $machines = \App\Models\Machine::with([
        'schedules',
        'components',
        'picMesin',
        'records.actions',
        'records.technician',
        'records.approvals.approver',
        'records.latestApproval',
    ])->get();
    $schedules = \App\Models\MaintenanceSchedule::with('machine')->get();
    $notifications = \App\Models\MaintenanceSchedule::with('machine')
        ->where('is_active', true)
        ->where('next_due_date', '<=', $tomorrow)
        ->get();

    return Inertia::render('Dashboard', [
        'machines' => $machines,
        'schedules' => $schedules,
        'notifications' => $notifications
    ]);
});

// Halaman Logs menggunakan Inertia dengan data logs langsung dikirim sebagai props
Route::get('/logs', function () {
    $logs = ActivityLog::with(['user'])
        ->orderBy('created_at', 'desc')
        ->get();

    return Inertia::render('Logs', [
        'logs' => $logs
    ]);
});

// Halaman Users menggunakan Inertia dengan data users langsung dikirim sebagai props
Route::get('/users', function () {
    $users = \App\Models\User::orderBy('full_name')->get();

    return Inertia::render('Users', [
        'users' => $users
    ]);
});

// Halaman Machines menggunakan Inertia dengan data langsung dikirim sebagai props
Route::get('/machines', function () {
    $tomorrow = \Carbon\Carbon::tomorrow();

    $machines = \App\Models\Machine::with(['schedules', 'components', 'picMesin', 'records.actions'])->get();
    $schedules = \App\Models\MaintenanceSchedule::with('machine')->get();
    $notifications = \App\Models\MaintenanceSchedule::with('machine')
        ->where('is_active', true)
        ->where('next_due_date', '<=', $tomorrow)
        ->get();

    return Inertia::render('Machines', [
        'machines' => $machines,
        'schedules' => $schedules,
        'notifications' => $notifications
    ]);
});

// Halaman Machine Detail menggunakan Inertia dengan data mesin dikirim sebagai props
Route::get('/machine/{id}', function ($id) {
    $machine = \App\Models\Machine::with([
        'schedules',
        'components.indicators',
        'records.actions.component.indicators',
        'records.actions.indicatorValues.indicator',
        'records.technician',
        'records.approvals.approver',
        'records.latestApproval',
        'records.machine',
        'picMesin'
    ])->findOrFail($id);

    return Inertia::render('MachineDetail', [
        'machine' => $machine
    ]);
});

// Halaman Baru Pembuatan Laporan (Report Page) kustom
Route::get('/report', function () {
    return Inertia::render('Report');
});

// Halaman Approval menggunakan Inertia
Route::get('/approvals', function () {
    return Inertia::render('Approval');
});

// Menggunakan view lama agar fungsi-fungsi SPA lama berjalan normal dahulu
Route::get('/{any}', function () {
    return view('dashboard');
})->where('any', '.*');
