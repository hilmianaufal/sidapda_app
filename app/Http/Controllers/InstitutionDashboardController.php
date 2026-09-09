<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\StudentEnrollment;

class InstitutionDashboardController extends Controller
{
    public function show(Institution $institution)
    {
        abort_unless($institution->is_active, 404);

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');

        $academicYear = now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;

        $enrollments = StudentEnrollment::query()
            ->where('institution_id', $institution->id)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('is_active', true));

        $totalStudents = (clone $enrollments)->count();
        $totalClasses = $institution->code === 'madad'
            ? (clone $enrollments)
                ->whereIn('level', StudentEnrollment::madadLevels())
                ->whereNotNull('class_name')
                ->where('class_name', '!=', '')
                ->get(['level', 'class_name'])
                ->unique(fn (StudentEnrollment $enrollment) => $enrollment->level.'|'.$enrollment->class_name)
                ->count()
            : (clone $enrollments)
                ->whereNotNull('class_name')
                ->where('class_name', '!=', '')
                ->distinct()
                ->count('class_name');

        $unassignedClassCount = (clone $enrollments)
            ->where(function ($query) use ($institution) {
                $query->whereNull('class_name')
                    ->orWhere('class_name', '');

                if ($institution->code === 'madad') {
                    $query->orWhereNull('level')
                        ->orWhereNotIn('level', StudentEnrollment::madadLevels());
                }
            })
            ->count();

        $totalUsers = $institution->users()
            ->wherePivot('is_active', true)
            ->where('users.is_active', true)
            ->count();

        $studentsByClass = $institution->code === 'madad'
            ? (clone $enrollments)
                ->selectRaw("COALESCE(NULLIF(level, ''), 'Belum ada jenjang') as level_label, COALESCE(NULLIF(class_name, ''), 'Belum ada kelas') as class_label, COUNT(*) as total")
                ->groupBy('level_label', 'class_label')
                ->orderByRaw("FIELD(level_label, 'Ula', 'Wustha', 'Ulya', 'Belum ada jenjang')")
                ->orderBy('class_label')
                ->get()
            : (clone $enrollments)
                ->selectRaw("COALESCE(NULLIF(class_name, ''), 'Belum ada kelas') as class_label, COUNT(*) as total")
                ->groupBy('class_label')
                ->orderBy('class_label')
                ->get();

        $modules = $this->modulesFor($institution);

        return view('institutions.dashboard', compact(
            'institution',
            'academicYear',
            'totalStudents',
            'totalClasses',
            'totalUsers',
            'unassignedClassCount',
            'studentsByClass',
            'modules'
        ));
    }

    private function modulesFor(Institution $institution): array
    {
        $modules = match ($institution->code) {
            'ponpes' => [
                ['label' => 'Absensi Salat', 'icon' => 'bi-qr-code-scan', 'route' => 'scan.index', 'ready' => true],
                ['label' => 'Kegiatan Pondok', 'icon' => 'bi-calendar-check', 'route' => 'activities.scan', 'ready' => true],
                ['label' => 'Pulang / Kembali', 'icon' => 'bi-house-door', 'route' => 'boarding-movements.index', 'ready' => true],
                ['label' => 'Izin Kegiatan Pondok', 'icon' => 'bi-file-earmark-check', 'route' => 'activities.excuses.pondok', 'ready' => true],
                ['label' => 'Rekap Pondok', 'icon' => 'bi-clipboard-data', 'route' => 'rekap.index', 'ready' => true],
            ],
            'mi' => [
                [
                    'label' => 'Absensi Siswa MI',
                    'icon' => 'bi-person-check',
                    'route' => 'school-attendance.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Izin & Sakit Siswa MI',
                    'icon' => 'bi-file-earmark-medical',
                    'route' => 'school-attendance.excuses.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Absensi Siswa MI',
                    'icon' => 'bi-clipboard-data',
                    'route' => 'school-attendance.reports.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Data Guru MI',
                    'icon' => 'bi-person-vcard',
                    'route' => 'school-teachers.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Absensi Guru MI',
                    'icon' => 'bi-person-badge',
                    'route' => 'school-teacher-attendance.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Izin & Sakit Guru MI',
                    'icon' => 'bi-file-earmark-medical',
                    'route' => 'school-teacher-attendance.excuses.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Absensi Guru MI',
                    'icon' => 'bi-clipboard-data',
                    'route' => 'school-teacher-attendance.reports.index',
                    'params' => ['institution' => 'mi'],
                    'ready' => true,
                ],
                [
                    'label' => 'Pengaturan Waktu & Zona',
                    'icon' => 'bi-clock-history',
                    'route' => 'school-attendance-settings.edit',
                    'params' => ['institution' => 'mi'],
                    'permission' => 'manage_users',
                    'ready' => true,
                ],
            ],
            'mts', 'ma' => [
                [
                    'label' => 'Absensi Siswa '.$institution->short_name,
                    'icon' => 'bi-person-check',
                    'route' => 'school-attendance.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Izin & Sakit Siswa',
                    'icon' => 'bi-file-earmark-medical',
                    'route' => 'school-attendance.excuses.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Absensi Siswa',
                    'icon' => 'bi-clipboard-data',
                    'route' => 'school-attendance.reports.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Data Guru',
                    'icon' => 'bi-person-vcard',
                    'route' => 'school-teachers.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Absensi Guru',
                    'icon' => 'bi-person-badge',
                    'route' => 'school-teacher-attendance.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Izin & Sakit Guru',
                    'icon' => 'bi-file-earmark-medical',
                    'route' => 'school-teacher-attendance.excuses.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Absensi Guru',
                    'icon' => 'bi-clipboard-data',
                    'route' => 'school-teacher-attendance.reports.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Data Ekstrakurikuler',
                    'icon' => 'bi-trophy',
                    'route' => 'school-extracurriculars.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Absensi Ekstrakurikuler',
                    'icon' => 'bi-qr-code-scan',
                    'route' => 'school-extracurricular-attendance.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Izin & Sakit Ekstrakurikuler',
                    'icon' => 'bi-file-earmark-medical',
                    'route' => 'school-extracurricular-attendance.excuses.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Absensi Ekstrakurikuler',
                    'icon' => 'bi-clipboard2-check',
                    'route' => 'school-extracurricular-attendance.reports.index',
                    'params' => ['institution' => $institution->code],
                    'ready' => true,
                ],
                [
                    'label' => 'Pengaturan Waktu & Zona',
                    'icon' => 'bi-clock-history',
                    'route' => 'school-attendance-settings.edit',
                    'params' => ['institution' => $institution->code],
                    'permission' => 'manage_users',
                    'ready' => true,
                ],
            ],
            'madad' => [
                [
                    'label' => 'Scan Absensi MADAD',
                    'icon' => 'bi-qr-code-scan',
                    'route' => 'activities.scan',
                    'params' => ['category' => 'diniyah'],
                    'ready' => true,
                ],
                [
                    'label' => 'Jadwal Kegiatan MADAD',
                    'icon' => 'bi-book',
                    'route' => 'activities.index',
                    'params' => ['category' => 'diniyah'],
                    'ready' => true,
                ],
                [
                    'label' => 'Data Siswa MADAD',
                    'icon' => 'bi-people',
                    'route' => 'students.index',
                    'params' => ['institution_id' => $institution->id],
                    'ready' => true,
                ],
                [
                    'label' => 'Izin Kegiatan MADAD',
                    'icon' => 'bi-file-earmark-check',
                    'route' => 'activities.excuses.madad',
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Harian MADAD',
                    'icon' => 'bi-clipboard-data',
                    'route' => 'rekap-diniyah.daily',
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Mingguan MADAD',
                    'icon' => 'bi-calendar-week',
                    'route' => 'rekap-diniyah.weekly',
                    'ready' => true,
                ],
                [
                    'label' => 'Rekap Bulanan MADAD',
                    'icon' => 'bi-calendar3',
                    'route' => 'rekap-diniyah.monthly',
                    'ready' => true,
                ],
            ],
            default => [],
        };

        $modules[] = [
            'label' => 'Profil & WhatsApp',
            'icon' => 'bi-building-gear',
            'route' => 'institution-settings.edit',
            'params' => ['institution' => $institution->code],
            'settings_access' => true,
            'ready' => true,
        ];

        return $modules;
    }
}
