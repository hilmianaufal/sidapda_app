<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityRecapController;
use App\Http\Controllers\ActivityScanController;
use App\Http\Controllers\ActivitySummaryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardingMovementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstitutionDashboardController;
use App\Http\Controllers\InstitutionSettingController;
use App\Http\Controllers\MonthlyRecapController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\PrayerSummaryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrScanController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\SchoolAttendanceController;
use App\Http\Controllers\SchoolAttendanceExcuseController;
use App\Http\Controllers\SchoolAttendanceReportController;
use App\Http\Controllers\SchoolAttendanceSettingController;
use App\Http\Controllers\SchoolExtracurricularAttendanceController;
use App\Http\Controllers\SchoolExtracurricularAttendanceExcuseController;
use App\Http\Controllers\SchoolExtracurricularAttendanceReportController;
use App\Http\Controllers\SchoolExtracurricularController;
use App\Http\Controllers\SchoolTeacherController;
use App\Http\Controllers\SchoolTeacherAttendanceController;
use App\Http\Controllers\SchoolTeacherAttendanceExcuseController;
use App\Http\Controllers\SchoolTeacherAttendanceReportController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentInstitutionController;
use App\Http\Controllers\StudentPromotionController;
use App\Http\Controllers\StudentQrController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WeeklyRecapController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'institution.scope'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:view_reports')
        ->name('dashboard');

    Route::get('/dashboard/{institution}', [InstitutionDashboardController::class, 'show'])
        ->middleware('permission:view_reports')
        ->name('dashboard.institution');

    Route::get('/dashboard/{institution}/pengaturan-lembaga', [InstitutionSettingController::class, 'edit'])
        ->name('institution-settings.edit');
    Route::put('/dashboard/{institution}/pengaturan-lembaga', [InstitutionSettingController::class, 'update'])
        ->name('institution-settings.update');
    Route::post('/dashboard/{institution}/pengaturan-lembaga/cek-fonnte', [InstitutionSettingController::class, 'test'])
        ->name('institution-settings.fonnte.test');
    Route::post('/dashboard/{institution}/pengaturan-lembaga/kirim-tes-fonnte', [InstitutionSettingController::class, 'sendTest'])
        ->name('institution-settings.fonnte.send-test');



    /*
    |--------------------------------------------------------------------------
    | Scan QR - Petugas & Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:scan_qr')->group(function () {
        Route::get('/scan', [QrScanController::class, 'index'])->name('scan.index');
        Route::post('/scan', [QrScanController::class, 'store'])->name('scan.store');

        Route::get('/kegiatan/scan', [ActivityScanController::class, 'index'])->name('activities.scan');
        Route::post('/kegiatan/scan', [ActivityScanController::class, 'store'])->name('activities.scan.store');

        Route::get('/pondok/pulang-kembali', [BoardingMovementController::class, 'index'])
            ->name('boarding-movements.index');
        Route::post('/pondok/pulang-kembali', [BoardingMovementController::class, 'store'])
            ->name('boarding-movements.store');

        Route::get('/dashboard/{institution}/absensi-ekstrakurikuler', [SchoolExtracurricularAttendanceController::class, 'index'])
            ->name('school-extracurricular-attendance.index');
        Route::post('/dashboard/{institution}/absensi-ekstrakurikuler', [SchoolExtracurricularAttendanceController::class, 'store'])
            ->name('school-extracurricular-attendance.store');

        Route::get('/dashboard/{institution}/absensi-ekstrakurikuler/izin-sakit', [SchoolExtracurricularAttendanceExcuseController::class, 'index'])
            ->name('school-extracurricular-attendance.excuses.index');
        Route::post('/dashboard/{institution}/absensi-ekstrakurikuler/izin-sakit', [SchoolExtracurricularAttendanceExcuseController::class, 'store'])
            ->name('school-extracurricular-attendance.excuses.store');
        Route::get('/dashboard/{institution}/absensi-ekstrakurikuler/izin-sakit/{excuse}/lampiran', [SchoolExtracurricularAttendanceExcuseController::class, 'download'])
            ->name('school-extracurricular-attendance.excuses.download');
        Route::delete('/dashboard/{institution}/absensi-ekstrakurikuler/izin-sakit/{excuse}', [SchoolExtracurricularAttendanceExcuseController::class, 'destroy'])
            ->name('school-extracurricular-attendance.excuses.destroy');

        Route::get('/dashboard/{institution}/absensi-siswa', [SchoolAttendanceController::class, 'index'])
            ->name('school-attendance.index');
        Route::post('/dashboard/{institution}/absensi-siswa', [SchoolAttendanceController::class, 'store'])
            ->name('school-attendance.store');

        Route::get('/dashboard/{institution}/absensi-siswa/izin-sakit', [SchoolAttendanceExcuseController::class, 'index'])
            ->name('school-attendance.excuses.index');
        Route::post('/dashboard/{institution}/absensi-siswa/izin-sakit', [SchoolAttendanceExcuseController::class, 'store'])
            ->name('school-attendance.excuses.store');
        Route::get('/dashboard/{institution}/absensi-siswa/izin-sakit/{excuse}/lampiran', [SchoolAttendanceExcuseController::class, 'download'])
            ->name('school-attendance.excuses.download');
        Route::delete('/dashboard/{institution}/absensi-siswa/izin-sakit/{excuse}', [SchoolAttendanceExcuseController::class, 'destroy'])
            ->name('school-attendance.excuses.destroy');

        Route::get('/dashboard/{institution}/absensi-guru', [SchoolTeacherAttendanceController::class, 'index'])
            ->name('school-teacher-attendance.index');
        Route::post('/dashboard/{institution}/absensi-guru', [SchoolTeacherAttendanceController::class, 'store'])
            ->name('school-teacher-attendance.store');

        Route::get('/dashboard/{institution}/absensi-guru/izin-sakit', [SchoolTeacherAttendanceExcuseController::class, 'index'])
            ->name('school-teacher-attendance.excuses.index');
        Route::post('/dashboard/{institution}/absensi-guru/izin-sakit', [SchoolTeacherAttendanceExcuseController::class, 'store'])
            ->name('school-teacher-attendance.excuses.store');
        Route::get('/dashboard/{institution}/absensi-guru/izin-sakit/{excuse}/lampiran', [SchoolTeacherAttendanceExcuseController::class, 'download'])
            ->name('school-teacher-attendance.excuses.download');
        Route::delete('/dashboard/{institution}/absensi-guru/izin-sakit/{excuse}', [SchoolTeacherAttendanceExcuseController::class, 'destroy'])
            ->name('school-teacher-attendance.excuses.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Santri
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:manage_students')->group(function () {
        Route::get('/students/search/realtime', [StudentController::class, 'searchRealtime'])
            ->name('students.search.realtime');

        Route::get('/students/promotions', [StudentPromotionController::class, 'index'])
            ->name('students.promotions.index');
        Route::post('/students/promotions', [StudentPromotionController::class, 'store'])
            ->name('students.promotions.store');

        Route::resource('students', StudentController::class);

        Route::get('/students/{student}/institutions', [StudentInstitutionController::class, 'edit'])
            ->name('students.institutions.edit');
        Route::put('/students/{student}/institutions', [StudentInstitutionController::class, 'update'])
            ->name('students.institutions.update');

        Route::get('/students-import', [StudentController::class, 'importForm'])
            ->name('students.import.form');
        Route::post('/students-import', [StudentController::class, 'import'])
            ->name('students.import');
        Route::get('/students-import/template', [StudentController::class, 'downloadTemplate'])
            ->name('students.import.template');
        Route::get('/students/export/excel', [StudentController::class, 'exportStudents'])
            ->name('students.export.excel');

        Route::get('/dashboard/{institution}/guru-sekolah-pagi', [SchoolTeacherController::class, 'index'])
            ->name('school-teachers.index');
        Route::post('/dashboard/{institution}/guru-sekolah-pagi', [SchoolTeacherController::class, 'store'])
            ->name('school-teachers.store');
        Route::post('/dashboard/{institution}/guru-sekolah-pagi/import', [SchoolTeacherController::class, 'import'])
            ->name('school-teachers.import');
        Route::get('/dashboard/{institution}/guru-sekolah-pagi/template', [SchoolTeacherController::class, 'template'])
            ->name('school-teachers.template');
        Route::get('/dashboard/{institution}/guru-sekolah-pagi/export', [SchoolTeacherController::class, 'export'])
            ->name('school-teachers.export');
        Route::get('/dashboard/{institution}/guru-sekolah-pagi/{teacher}/edit', [SchoolTeacherController::class, 'edit'])
            ->name('school-teachers.edit');
        Route::put('/dashboard/{institution}/guru-sekolah-pagi/{teacher}', [SchoolTeacherController::class, 'update'])
            ->name('school-teachers.update');
        Route::delete('/dashboard/{institution}/guru-sekolah-pagi/{teacher}', [SchoolTeacherController::class, 'destroy'])
            ->name('school-teachers.destroy');
        Route::get('/dashboard/{institution}/guru-sekolah-pagi/{teacher}/qr', [SchoolTeacherController::class, 'qr'])
            ->name('school-teachers.qr');
        Route::get('/dashboard/{institution}/guru-sekolah-pagi/{teacher}/qr/download', [SchoolTeacherController::class, 'downloadQr'])
            ->name('school-teachers.qr.download');
    });

    Route::middleware('permission:manage_prayers')->group(function () {
        Route::get('/jadwal-sholat', [PrayerController::class, 'index'])->name('prayers.index');
        Route::get('/jadwal-sholat/create', [PrayerController::class, 'create'])->name('prayers.create');
        Route::post('/jadwal-sholat', [PrayerController::class, 'store'])->name('prayers.store');
        Route::get('/jadwal-sholat/{prayer}/edit', [PrayerController::class, 'edit'])->name('prayers.edit');
        Route::put('/jadwal-sholat/{prayer}', [PrayerController::class, 'update'])->name('prayers.update');
        Route::delete('/jadwal-sholat/{prayer}', [PrayerController::class, 'destroy'])->name('prayers.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Santri QR & ID Card
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:manage_students')->group(function () {
        Route::get('/students/{student}/qr', [StudentQrController::class, 'show'])
            ->name('students.qr.show');

        Route::get('/students/{student}/qr/download', [StudentQrController::class, 'download'])
            ->name('students.qr.download');

        Route::get('/students/{student}/id-card', [StudentQrController::class, 'idCard'])
            ->name('students.id-card');

        Route::get('/students/{student}/attendance', [StudentAttendanceController::class, 'show'])
            ->name('students.attendance.show');

        Route::get('/students/{student}/id-card/png', [StudentQrController::class, 'idCardPng'])
            ->name('students.id-card.png');
    });

    /*
    |--------------------------------------------------------------------------
    | Kegiatan
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:manage_activities')->group(function () {
        Route::get('/kegiatan', [ActivityController::class, 'index'])->name('activities.index');
        Route::get('/kegiatan/create', [ActivityController::class, 'create'])->name('activities.create');
        Route::post('/kegiatan', [ActivityController::class, 'store'])->name('activities.store');
        Route::get('/kegiatan/{activity}/edit', [ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/kegiatan/{activity}', [ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/kegiatan/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');

        Route::get('/dashboard/{institution}/ekstrakurikuler', [SchoolExtracurricularController::class, 'index'])
            ->name('school-extracurriculars.index');
        Route::post('/dashboard/{institution}/ekstrakurikuler', [SchoolExtracurricularController::class, 'store'])
            ->name('school-extracurriculars.store');
        Route::get('/dashboard/{institution}/ekstrakurikuler/{extracurricular}/edit', [SchoolExtracurricularController::class, 'edit'])
            ->name('school-extracurriculars.edit');
        Route::put('/dashboard/{institution}/ekstrakurikuler/{extracurricular}', [SchoolExtracurricularController::class, 'update'])
            ->name('school-extracurriculars.update');
        Route::delete('/dashboard/{institution}/ekstrakurikuler/{extracurricular}', [SchoolExtracurricularController::class, 'destroy'])
            ->name('school-extracurriculars.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Rekap Sholat
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/dashboard/{institution}/rekap-absensi-ekstrakurikuler', [SchoolExtracurricularAttendanceReportController::class, 'index'])
            ->name('school-extracurricular-attendance.reports.index');
        Route::get('/dashboard/{institution}/rekap-absensi-ekstrakurikuler/export', [SchoolExtracurricularAttendanceReportController::class, 'export'])
            ->name('school-extracurricular-attendance.reports.export');
        Route::get('/dashboard/{institution}/rekap-absensi-ekstrakurikuler/izin-sakit/{excuse}/lampiran', [SchoolExtracurricularAttendanceExcuseController::class, 'download'])
            ->name('school-extracurricular-attendance.reports.excuses.download');

        Route::get('/dashboard/{institution}/rekap-absensi-siswa', [SchoolAttendanceReportController::class, 'index'])
            ->name('school-attendance.reports.index');
        Route::get('/dashboard/{institution}/rekap-absensi-siswa/export', [SchoolAttendanceReportController::class, 'export'])
            ->name('school-attendance.reports.export');
        Route::get('/dashboard/{institution}/rekap-absensi-siswa/izin-sakit/{excuse}/lampiran', [SchoolAttendanceExcuseController::class, 'download'])
            ->name('school-attendance.reports.excuses.download');

        Route::get('/dashboard/{institution}/rekap-absensi-guru', [SchoolTeacherAttendanceReportController::class, 'index'])
            ->name('school-teacher-attendance.reports.index');
        Route::get('/dashboard/{institution}/rekap-absensi-guru/export', [SchoolTeacherAttendanceReportController::class, 'export'])
            ->name('school-teacher-attendance.reports.export');
        Route::get('/dashboard/{institution}/rekap-absensi-guru/izin-sakit/{excuse}/lampiran', [SchoolTeacherAttendanceExcuseController::class, 'download'])
            ->name('school-teacher-attendance.reports.excuses.download');

        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/export/excel', [RekapController::class, 'exportExcel'])->name('rekap.export.excel');
        Route::get('/rekap/export/pdf', [RekapController::class, 'exportPdf'])->name('rekap.export.pdf');

        Route::get('/rekap-bulanan', [MonthlyRecapController::class, 'index'])->name('rekap.monthly');
        Route::get('/rekap-bulanan/export/excel', [MonthlyRecapController::class, 'exportExcel'])->name('rekap.monthly.export.excel');
        Route::get('/rekap-bulanan/export/pdf', [MonthlyRecapController::class, 'exportPdf'])->name('rekap.monthly.export.pdf');
        Route::post('/rekap/status', [RekapController::class, 'markStatus'])
                    ->name('rekap.mark-status')
                    ->middleware(['auth','permission:view_reports']);
        Route::post('/rekap/status/cancel', [RekapController::class, 'cancelStatus'])
            ->name('rekap.cancel-status')
            ->middleware(['auth','permission:view_reports']);
        Route::get('/rekap-salat', [PrayerSummaryController::class, 'daily'])
    ->name('rekap.prayer-summary.daily');
    Route::get('/rekap-salat/mingguan', [PrayerSummaryController::class, 'weekly'])
    ->name('rekap.prayer-summary.weekly');
    Route::get('/rekap-salat/bulanan', [PrayerSummaryController::class, 'monthly'])
    ->name('rekap.prayer-summary.monthly');
    });

    /*
    |--------------------------------------------------------------------------
    | Rekap Kegiatan
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/rekap-kegiatan-detail', [ActivityRecapController::class, 'index'])
            ->name('activities.recap');

        Route::get('/izin-kegiatan-pondok', [ActivityRecapController::class, 'index'])
            ->defaults('category', 'umum')
            ->name('activities.excuses.pondok');

        Route::get('/izin-kegiatan-madad', [ActivityRecapController::class, 'index'])
            ->defaults('category', 'diniyah')
            ->name('activities.excuses.madad');

        Route::get('/rekap-kegiatan/export/excel', [ActivityRecapController::class, 'exportExcel'])
            ->name('activities.recap.export.excel');

        Route::post('/rekap-kegiatan/status', [ActivityRecapController::class, 'markStatus'])
            ->name('activities.recap.mark-status');
        Route::post('/rekap-kegiatan/status/cancel', [ActivityRecapController::class, 'cancelStatus'])
            ->name('activities.recap.cancel-status');
    });

    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/rekap-mingguan', [WeeklyRecapController::class, 'index'])
            ->name('rekap.weekly');
        Route::get('/rekap-mingguan/export/excel', [WeeklyRecapController::class, 'exportExcel'])
            ->name('rekap.weekly.export.excel');

        Route::get('/rekap-kegiatan', [ActivitySummaryController::class, 'daily'])
            ->name('rekap-kegiatan.daily');
        Route::get('/rekap-kegiatan/mingguan', [ActivitySummaryController::class, 'weekly'])
            ->name('rekap-kegiatan.weekly');
        Route::get('/rekap-kegiatan/bulanan', [ActivitySummaryController::class, 'monthly'])
            ->name('rekap-kegiatan.monthly');
        Route::get('/rekap-kegiatan/export/{period}', [ActivitySummaryController::class, 'exportExcel'])
            ->defaults('category', 'umum')
            ->name('rekap-kegiatan.export.excel');

        Route::get('/rekap-diniyah', [ActivitySummaryController::class, 'dailyDiniyah'])
            ->name('rekap-diniyah.daily');
        Route::get('/rekap-diniyah/mingguan', [ActivitySummaryController::class, 'diniyahWeekly'])
            ->name('rekap-diniyah.weekly');
        Route::get('/rekap-diniyah/bulanan', [ActivitySummaryController::class, 'diniyahMonthly'])
            ->name('rekap-diniyah.monthly');
        Route::get('/rekap-diniyah/export/{period}', [ActivitySummaryController::class, 'exportExcel'])
            ->defaults('category', 'diniyah')
            ->name('rekap-diniyah.export.excel');

        Route::get('/rekap-salat/export/{period}', [PrayerSummaryController::class, 'exportExcel'])
            ->name('rekap.prayer-summary.export.excel');
    });
    /*




    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/dashboard/{institution}/pengaturan-waktu', [SchoolAttendanceSettingController::class, 'edit'])
            ->name('school-attendance-settings.edit');
        Route::put('/dashboard/{institution}/pengaturan-waktu', [SchoolAttendanceSettingController::class, 'update'])
            ->name('school-attendance-settings.update');

        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
});
