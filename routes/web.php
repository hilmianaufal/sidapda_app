<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityRecapController;
use App\Http\Controllers\ActivityScanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonthlyRecapController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrScanController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentQrController;
use App\Http\Controllers\UserManagementController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/tes-waktu', function (){
    return Carbon::now('Asia/Makassar')->format('Y-m-d H:i:s');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Dashboard & rekap boleh untuk yang punya view_reports
Route::middleware(['auth', 'permission:view_reports'])->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/rekap', [\App\Http\Controllers\RekapController::class, 'index'])->name('rekap.index');
    Route::get('/rekap/export/excel', [\App\Http\Controllers\RekapController::class, 'exportExcel'])->name('rekap.export.excel');
    Route::get('/rekap/export/pdf', [\App\Http\Controllers\RekapController::class, 'exportPdf'])->name('rekap.export.pdf');

    Route::get('/rekap-bulanan', [\App\Http\Controllers\MonthlyRecapController::class, 'index'])->name('rekap.monthly');
    Route::get('/rekap-bulanan/export/excel', [\App\Http\Controllers\MonthlyRecapController::class, 'exportExcel'])->name('rekap.monthly.export.excel');
    Route::get('/rekap-bulanan/export/pdf', [\App\Http\Controllers\MonthlyRecapController::class, 'exportPdf'])->name('rekap.monthly.export.pdf');
});


// Manage jadwal sholat hanya admin
Route::middleware(['auth', 'permission:manage_prayers'])->group(function () {
    Route::get('/jadwal-sholat', [\App\Http\Controllers\PrayerController::class, 'index'])->name('prayers.index');
    Route::get('/jadwal-sholat/{prayer}/edit', [\App\Http\Controllers\PrayerController::class, 'edit'])->name('prayers.edit');
    Route::put('/jadwal-sholat/{prayer}', [\App\Http\Controllers\PrayerController::class, 'update'])->name('prayers.update');
});
Route::get('/students/{student}/qr', [StudentQrController::class, 'show'])
    ->name('students.qr.show');

Route::get('/students/{student}/qr/download', [StudentQrController::class, 'download'])
    ->name('students.qr.download');

Route::middleware(['auth', 'permission:manage_students'])->group(function () {
    Route::resource('students', \App\Http\Controllers\StudentController::class);
});

Route::middleware(['auth', 'permission:scan_qr'])->group(function () {
    Route::get('/scan', [\App\Http\Controllers\QrScanController::class, 'index'])->name('scan.index');
    Route::post('/scan', [\App\Http\Controllers\QrScanController::class, 'store'])->name('scan.store');
});

Route::get('/students/{student}/attendance', [StudentAttendanceController::class, 'show'])->name('students.attendance.show');

Route::middleware(['auth', 'permission:manage_users'])->group(function () {
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'permission:scan_qr'])->group(function () {
    Route::get('/kegiatan/scan', [ActivityScanController::class, 'index'])
        ->name('activities.scan');

    Route::post('/kegiatan/scan', [ActivityScanController::class, 'store'])
        ->name('activities.scan.store');
});
Route::middleware(['auth', 'permission:manage_prayers'])->group(function () {
    Route::get('/kegiatan', [ActivityController::class, 'index'])
        ->name('activities.index');

    Route::get('/kegiatan/{activity}/edit', [ActivityController::class, 'edit'])
        ->name('activities.edit');

    Route::put('/kegiatan/{activity}', [ActivityController::class, 'update'])
        ->name('activities.update');
});

Route::middleware(['auth', 'permission:view_reports'])->group(function () {
    Route::get('/rekap-kegiatan', [ActivityRecapController::class, 'index'])
        ->name('activities.recap');
});


Route::get('/kegiatan/create', [ActivityController::class, 'create'])
    ->name('activities.create');

Route::post('/kegiatan', [ActivityController::class, 'store'])
    ->name('activities.store');
Route::delete('/kegiatan/{activity}', [ActivityController::class, 'destroy'])
    ->name('activities.destroy');
Route::get('/rekap-kegiatan/export/excel', [ActivityRecapController::class, 'exportExcel'])
    ->name('activities.recap.export.excel');
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
});

Route::post('/rekap-kegiatan/status', [ActivityRecapController::class, 'markStatus'])
    ->name('activities.recap.mark-status');
