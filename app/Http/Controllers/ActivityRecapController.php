<?php

namespace App\Http\Controllers;

use App\Exports\ActivityRecapExport;
use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivitySession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Institution;
use App\Services\StudentWhatsappNotifier;
use Maatwebsite\Excel\Facades\Excel;

class ActivityRecapController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $activityId = $request->input('activity_id');
        $level = StudentEnrollment::normalizeMadadLevel($request->input('level'));
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');
        $requestedCategory = $request->input('category', $request->route('category'));
        $category = in_array($requestedCategory, ['umum', 'diniyah'], true)
            ? $requestedCategory
            : 'umum';

        $activities = Activity::query()
            ->where('category', $category)
            ->orderBy('order')
            ->get();

        $selectedActivity = $activityId
            ? $activities->firstWhere('id', (int) $activityId)
            : $activities->first();

        $academicYear = Student::academicYearForDate($date);
        $obligationAt = $selectedActivity
            ? Carbon::parse($date.' '.$selectedActivity->start_time)
            : Carbon::parse($date)->endOfDay();

        $kelasList = $this->classOptions($category, $academicYear, $obligationAt);
        $levelList = $category === 'diniyah' ? StudentEnrollment::madadLevels() : [];

        $kamarList = Student::query()
            ->obligatedForActivity($category, $academicYear, $obligationAt)
            ->whereNotNull('kamar')
            ->distinct()
            ->orderBy('kamar')
            ->pluck('kamar');

        $studentsQuery = $this->studentQueryForCategory(
            Student::query()
            ->obligatedForActivity($category, $academicYear, $obligationAt)
                ->when($kamar, fn ($q) => $q->where('kamar', $kamar)),
            $category,
            $academicYear,
            $level,
            $kelas
        );

        $totalStudents = $selectedActivity ? (clone $studentsQuery)->count() : 0;

        $session = null;
        $attendances = collect();
        $absentStudents = $selectedActivity
            ? (clone $studentsQuery)->orderBy('name')->get()
            : collect();

        $hadirCount = 0;
        $terlambatCount = 0;
        $izinCount = 0;
        $sakitCount = 0;
        $pulangCount = 0;
        $belumCount = $totalStudents;

        if ($selectedActivity) {
            $session = ActivitySession::where('activity_id', $selectedActivity->id)
                ->whereDate('started_at', $date)
                ->latest('id')
                ->first();

            if ($session) {
                $attQuery = ActivityAttendance::query()
                    ->with(['student' => function ($student) use ($category, $academicYear) {
                        if ($category === 'diniyah') {
                            $student->withInstitutionClass('madad', $academicYear);
                        }
                    }])
                    ->where('activity_session_id', $session->id)
                    ->whereHas('student', fn ($student) => $student
                        ->obligatedForActivity($category, $academicYear, $obligationAt))
                    ->when($level || $kelas, function ($attendance) use ($category, $academicYear, $level, $kelas) {
                        $attendance->whereHas('student', function (Builder $student) use (
                            $category,
                            $academicYear,
                            $level,
                            $kelas
                        ) {
                            if ($category === 'diniyah') {
                                if ($level) {
                                    $student->whereInstitutionLevel('madad', $academicYear, $level);
                                }
                                if ($kelas) {
                                    $student->whereInstitutionClass('madad', $academicYear, $kelas);
                                }
                            } elseif ($kelas) {
                                $student->where('kelas', $kelas);
                            }
                        });
                    })
                    ->when($kamar, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kamar', $kamar)));

                $hadirCount = (clone $attQuery)->where('status', 'hadir')->count();
                $terlambatCount = (clone $attQuery)->where('status', 'terlambat')->count();
                $izinCount = (clone $attQuery)->where('status', 'izin')->count();
                $sakitCount = (clone $attQuery)->where('status', 'sakit')->count();
                $pulangCount = (clone $attQuery)->where('status', 'pulang')->count();

                $sudahCount = $hadirCount + $terlambatCount + $izinCount + $sakitCount + $pulangCount;
                $belumCount = max(0, $totalStudents - $sudahCount);

                $attendances = (clone $attQuery)
                    ->orderByDesc('scanned_at')
                    ->get();

                $presentIds = $attendances->pluck('student_id');

                $absentStudents = (clone $studentsQuery)
                    ->whereNotIn('id', $presentIds)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('activities.recap', compact(
            'date',
            'activityId',
            'level',
            'kelas',
            'kamar',
            'kelasList',
            'levelList',
            'kamarList',
            'activities',
            'selectedActivity',
            'session',
            'attendances',
            'absentStudents',
            'totalStudents',
            'hadirCount',
            'terlambatCount',
            'izinCount',
            'sakitCount',
            'pulangCount',
            'belumCount',
            'category',
            'academicYear'
        ));
    }

    public function exportExcel(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $activityId = (int) $request->input('activity_id');
        $level = StudentEnrollment::normalizeMadadLevel($request->input('level'));
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        $activity = Activity::findOrFail($activityId);

        $filename = 'Rekap-Kegiatan-' . $activity->name . '-' . $date
            . ($level ? '-Jenjang-' . $level : '')
            . ($kelas ? '-Jenjang-' . $kelas : '')
            . ($kamar ? '-Kamar-' . $kamar : '')
            . '.xlsx';

        return Excel::download(
            new ActivityRecapExport(
                $date,
                $activity->id,
                $activity->name,
                $level,
                $kelas,
                $kamar
            ),
            $filename
        );
    }

    public function markStatus(Request $request, StudentWhatsappNotifier $whatsapp)
    {
        $data = $request->validate([
            'activity_id' => ['required', 'exists:activities,id'],
            'student_id' => ['required', 'exists:students,id'],
            'date' => ['required', 'date'],
            'status' => ['required', 'in:izin,sakit,pulang'],
        ]);

        $activity = Activity::findOrFail($data['activity_id']);
        $student = Student::findOrFail($data['student_id']);
        $academicYear = Student::academicYearForDate($data['date']);
        $startedAt = Carbon::parse($data['date'] . ' ' . $activity->start_time);
        $endedAt = Carbon::parse($data['date'] . ' ' . $activity->end_time);

        if (! $student->isObligatedForActivity($activity->category, $academicYear, $startedAt)) {
            $message = $student->isAwayFromBoarding($startedAt)
                ? 'Siswa sedang berstatus pulang pada waktu kegiatan ini.'
                : ($activity->category === 'diniyah'
                    ? 'Siswa belum terdaftar aktif di MADAD tahun ajaran '.$academicYear.'.'
                    : 'Santri tidak mukim tidak memiliki kewajiban kegiatan Pondok.');

            return back()->with('error', $message);
        }

        $session = ActivitySession::where('activity_id', $activity->id)
            ->whereDate('started_at', $data['date'])
            ->latest('id')
            ->first();

        if (!$session) {
            $session = ActivitySession::create([
                'activity_id' => $activity->id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'status' => 'live',
            ]);
        }

        $attendance = ActivityAttendance::updateOrCreate(
            [
                'activity_session_id' => $session->id,
                'student_id' => $data['student_id'],
            ],
            [
                'scanned_at' => now(),
                'status' => $data['status'],
            ]
        );

        if ($attendance->wasRecentlyCreated || $attendance->wasChanged('status')) {
            $institution = Institution::query()
                ->where('code', $activity->category === 'diniyah' ? 'madad' : 'ponpes')
                ->first();

            if ($institution) {
                $whatsapp->excuse(
                    $institution,
                    $student,
                    $activity->name,
                    $data['status'],
                    $data['date']
                );
            }
        }

        return back()->with('success', 'Status santri berhasil diperbarui.');
    }

    public function cancelStatus(Request $request)
    {
        $data = $request->validate([
            'attendance_id' => ['required', 'exists:activity_attendances,id'],
        ]);

        $attendance = ActivityAttendance::findOrFail($data['attendance_id']);

        if (in_array($attendance->status, ['izin', 'sakit', 'pulang'])) {
            $attendance->delete();

            return back()->with('success', 'Status kegiatan berhasil dibatalkan.');
        }

        return back()->with('error', 'Absensi scan tidak bisa dibatalkan dari sini.');
    }

    private function studentQueryForCategory(
        Builder $query,
        string $category,
        string $academicYear,
        ?string $level,
        ?string $kelas
    ): Builder {
        if ($category === 'diniyah') {
            $query->withInstitutionClass('madad', $academicYear);

            if ($level) {
                $query->whereInstitutionLevel('madad', $academicYear, $level);
            }

            if ($kelas) {
                $query->whereInstitutionClass('madad', $academicYear, $kelas);
            }

            return $query;
        }

        return $query->when($kelas, fn (Builder $student) => $student->where('kelas', $kelas));
    }

    private function classOptions(string $category, string $academicYear, Carbon $obligationAt)
    {
        if ($category === 'diniyah') {
            return StudentEnrollment::query()
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->whereNotNull('class_name')
                ->where('class_name', '!=', '')
                ->whereHas('institution', fn (Builder $institution) => $institution
                    ->where('code', 'madad')
                    ->where('is_active', true))
                ->whereHas('student', fn (Builder $student) => $student
                    ->obligatedForActivity('diniyah', $academicYear, $obligationAt))
                ->distinct()
                ->orderBy('class_name')
                ->pluck('class_name');
        }

        return Student::query()
            ->obligatedForActivity('umum', $academicYear, $obligationAt)
            ->whereNotNull('kelas')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');
    }
}
