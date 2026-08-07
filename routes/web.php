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
    return Inertia::render('Dashboard');
});

// Halaman Logs menggunakan Inertia
Route::get('/logs', function () {
    return Inertia::render('Logs');
});

// Halaman Users menggunakan Inertia
Route::get('/users', function () {
    return Inertia::render('Users');
});

// Halaman Machines menggunakan Inertia
Route::get('/machines', function () {
    return Inertia::render('Machines');
});

// Halaman Machine Detail menggunakan Inertia
Route::get('/machine/{id}', function ($id) {
    return Inertia::render('MachineDetail', [
        'id' => $id
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

// Halaman Daftar Komponen menggunakan Inertia
Route::get('/components', function () {
    return Inertia::render('Components');
});

// Halaman Stock menggunakan Inertia
Route::get('/stock', function () {
    return Inertia::render('Stock');
});

// Halaman Notifikasi (admin) menggunakan Inertia
Route::get('/notifications', function () {
    return Inertia::render('Notifications');
});

// Menggunakan view lama agar fungsi-fungsi SPA lama berjalan normal dahulu
Route::get('/{any}', function () {
    return view('dashboard');
})->where('any', '.*');
