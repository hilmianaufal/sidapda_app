<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Institution;
use App\Models\Prayer;
use App\Models\Student;
use App\Services\PrayerTimeService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, PrayerTimeService $prayerService)
    {
        $today = now()->toDateString();
        $user = auth()->user();
        $accessCodes = $user->accessibleInstitutionCodes();
        $hasPonpesAccess = in_array('ponpes', $accessCodes, true);
        $hasMadadAccess = in_array('madad', $accessCodes, true);

        $prayers = $hasPonpesAccess
            ? Prayer::where('is_active', true)->orderBy('order')->get()
            : collect();
        $activePrayer = $hasPonpesAccess ? $prayerService->getActivePrayer() : null;
        $totalStudents = $hasPonpesAccess
            ? Student::query()->obligatedForPrayer()->count()
            : 0;

        $academicYear = now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;

        $institutionQuery = Institution::query()
            ->where('is_active', true)
            ->orderBy('sort_order');

        if (! $user->hasRole('admin')) {
            $institutionQuery->whereHas('users', function ($query) {
                $query->where('users.id', auth()->id())
                    ->where('institution_user.is_active', true);
            });
        }

        $institutions = $institutionQuery
            ->withCount(['enrollments as active_students_count' => function ($query) use ($academicYear) {
                $query->where('academic_year', $academicYear)
                    ->where('is_active', true)
                    ->whereHas('student', fn ($studentQuery) => $studentQuery->where('is_active', true));
            }])
            ->get();

        // Siapkan data ringkasan per sholat
        $items = $prayers->map(function ($p) use ($today, $totalStudents, $prayerService) {
            $session = AttendanceSession::firstOrCreate(
                ['date' => $today, 'prayer_id' => $p->id],
                ['status' => 'live']
            );

            $attendanceQuery = Attendance::query()
                ->where('attendance_session_id', $session->id)
                ->whereHas('student', fn ($student) => $student->obligatedForPrayer());

            $hadir = (clone $attendanceQuery)->where('status', 'hadir')->count();
            $telat = (clone $attendanceQuery)->where('status', 'terlambat')->count();

            $sudah = $hadir + $telat;
            $belum = max(0, $totalStudents - $sudah);

            return [
                'prayer' => $p,
                'session' => $session,
                'status' => $prayerService->getPrayerStatus($p), // soon/live/closed
                'hadir' => $hadir,
                'telat' => $telat,
                'belum' => $belum,
                'progress' => $totalStudents > 0 ? (int) round(($sudah / $totalStudents) * 100) : 0,
            ];
        });

        return view('dashboard', compact(
            'today',
            'prayers',
            'activePrayer',
            'totalStudents',
            'institutions',
            'academicYear',
            'items',
            'accessCodes',
            'hasPonpesAccess',
            'hasMadadAccess'
        ));
    }
}
