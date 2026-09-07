<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolExtracurricular;
use App\Models\SchoolExtracurricularAttendance;
use App\Models\SchoolExtracurricularAttendanceExcuse;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\StudentWhatsappNotifier;

class SchoolExtracurricularAttendanceController extends Controller
{
    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $academicYear = $this->currentAcademicYear();
        $today = today()->toDateString();
        $todayDay = now()->dayOfWeek;

        $extracurriculars = SchoolExtracurricular::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();

        $requestedId = $request->integer('extracurricular_id');
        $selectedExtracurricular = $extracurriculars->firstWhere('id', $requestedId)
            ?? $extracurriculars->firstWhere('schedule_day', $todayDay)
            ?? $extracurriculars->first();

        $totalStudents = 0;
        $present = 0;
        $late = 0;
        $excused = 0;
        $sick = 0;
        $otherExcuses = 0;
        $notPresent = 0;
        $todayAttendances = collect();
        $scanOpenLabel = null;
        $scheduledToday = false;

        if ($selectedExtracurricular) {
            $enrollmentQuery = StudentEnrollment::query()
                ->where('institution_id', $institution->id)
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->whereHas('student', fn ($query) => $query->where('is_active', true));

            $this->applyLevelScope($enrollmentQuery, $selectedExtracurricular->level);
            $totalStudents = $enrollmentQuery->count();

            $attendanceQuery = SchoolExtracurricularAttendance::query()
                ->where('institution_id', $institution->id)
                ->where('school_extracurricular_id', $selectedExtracurricular->id)
                ->whereDate('attendance_date', $today);

            $present = (clone $attendanceQuery)->count();
            $late = (clone $attendanceQuery)->where('status', 'terlambat')->count();

            $excuseQuery = SchoolExtracurricularAttendanceExcuse::query()
                ->where('institution_id', $institution->id)
                ->where('school_extracurricular_id', $selectedExtracurricular->id)
                ->whereDate('attendance_date', $today);

            $excused = (clone $excuseQuery)->where('status', 'izin')->count();
            $sick = (clone $excuseQuery)->where('status', 'sakit')->count();
            $otherExcuses = (clone $excuseQuery)->where('status', 'lainnya')->count();
            $notPresent = max(0, $totalStudents - $present - $excused - $sick - $otherExcuses);
            $todayAttendances = (clone $attendanceQuery)
                ->with('student')
                ->orderByDesc('scanned_at')
                ->limit(50)
                ->get();

            $start = Carbon::parse($today.' '.$selectedExtracurricular->start_time);
            $scanOpenLabel = $start->copy()->subHour()->format('H:i');
            $scheduledToday = $selectedExtracurricular->schedule_day === $todayDay;
        }

        return view('school-extracurricular-attendance.index', compact(
            'institution',
            'academicYear',
            'today',
            'extracurriculars',
            'selectedExtracurricular',
            'totalStudents',
            'present',
            'late',
            'excused',
            'sick',
            'otherExcuses',
            'notPresent',
            'todayAttendances',
            'scanOpenLabel',
            'scheduledToday'
        ));
    }

    public function store(
        Request $request,
        Institution $institution,
        StudentWhatsappNotifier $whatsapp
    ): JsonResponse
    {
        $this->authorizeInstitution($institution);

        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'extracurricular_id' => ['required', 'integer'],
        ]);

        $extracurricular = SchoolExtracurricular::query()
            ->where('id', $data['extracurricular_id'])
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->first();

        if (!$extracurricular) {
            return $this->error('Ekstrakurikuler tidak ditemukan atau sedang nonaktif.', 404);
        }

        $now = now();
        if ($extracurricular->schedule_day !== $now->dayOfWeek) {
            return $this->error(
                $extracurricular->name.' dijadwalkan hari '.$extracurricular->dayLabel().'. Scan hari ini tidak dibuka.'
            );
        }

        $start = Carbon::parse($now->toDateString().' '.$extracurricular->start_time);
        $end = Carbon::parse($now->toDateString().' '.$extracurricular->end_time);
        $scanOpen = $start->copy()->subHour();

        if ($now->lt($scanOpen)) {
            return $this->error('Scan dibuka pukul '.$scanOpen->format('H:i').'.');
        }

        if ($now->gt($end)) {
            return $this->error('Scan ditutup pukul '.$end->format('H:i').'.');
        }

        $token = trim($data['token']);
        $student = Student::query()
            ->where(function ($query) use ($token) {
                $query->where('qr_token', $token)
                    ->orWhere('nis', $token);
            })
            ->first();

        if (!$student) {
            return $this->error('QR/NIS tidak dikenal atau siswa tidak ditemukan.', 404);
        }

        if (!$student->is_active) {
            return $this->error('Siswa sedang nonaktif. Hubungi admin.');
        }

        $academicYear = $this->currentAcademicYear();
        $enrollment = StudentEnrollment::query()
            ->where('institution_id', $institution->id)
            ->where('student_id', $student->id)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->first();

        if (!$enrollment) {
            return $this->error(
                $student->name.' belum terdaftar aktif di '.$institution->short_name.' tahun ajaran '.$academicYear.'.'
            );
        }

        $studentLevel = $this->normalizeLevel($enrollment->level);
        if (!$studentLevel) {
            return $this->error(
                'Jenjang '.$student->name.' belum diisi MTs/MA pada data lembaga.'
            );
        }

        if (!$this->levelMatches($extracurricular->level, $studentLevel)) {
            return $this->error(
                $student->name.' berada di '.$this->levelLabel($studentLevel)
                .', sedangkan kegiatan ini untuk '.$extracurricular->levelLabel().'.'
            );
        }

        $excuse = SchoolExtracurricularAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->where('school_extracurricular_id', $extracurricular->id)
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

        if ($excuse) {
            return $this->error(
                $student->name.' sudah tercatat '.strtoupper($excuse->status)
                .' untuk '.$extracurricular->name.' dan tidak dapat melakukan scan.'
            );
        }

        $lateThreshold = $start->copy()->addMinutes($extracurricular->late_minutes);
        $status = $now->gt($lateThreshold) ? 'terlambat' : 'hadir';

        $attendance = SchoolExtracurricularAttendance::query()->firstOrCreate(
            [
                'school_extracurricular_id' => $extracurricular->id,
                'student_id' => $student->id,
                'attendance_date' => $now->toDateString(),
            ],
            [
                'institution_id' => $institution->id,
                'academic_year' => $academicYear,
                'nis_snapshot' => $student->nis,
                'level_snapshot' => $studentLevel,
                'class_name_snapshot' => $enrollment->class_name,
                'scanned_at' => $now,
                'status' => $status,
                'recorded_by' => auth()->id(),
            ]
        );
        $already = !$attendance->wasRecentlyCreated;

        $statusLabel = $this->statusLabel($attendance->status);

        if (! $already) {
            $whatsapp->attendance(
                $institution,
                $student,
                'Ekstrakurikuler',
                $extracurricular->name,
                $attendance->status,
                $attendance->scanned_at
            );
        }

        return response()->json([
            'ok' => true,
            'already' => $already,
            'message' => $already
                ? $student->name.' sudah absen '.$extracurricular->name.' hari ini.'
                : 'Absensi berhasil: '.$student->name.' ('.$statusLabel.').',
            'status' => $attendance->status,
            'status_label' => $statusLabel,
            'scanned_at' => $attendance->scanned_at?->format('H:i:s'),
            'extracurricular' => [
                'id' => $extracurricular->id,
                'name' => $extracurricular->name,
            ],
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'class_name' => $enrollment->class_name,
                'level' => $this->levelLabel($studentLevel),
                'photo_url' => $student->photoUrl(),
            ],
        ]);
    }

    private function applyLevelScope(Builder $query, string $activityLevel): void
    {
        if ($activityLevel === 'mts_ma') {
            $query->whereIn(DB::raw('LOWER(TRIM(level))'), [
                'mts',
                'madrasah tsanawiyah',
                'ma',
                'aliyah',
                'madrasah aliyah',
                'mts_ma',
                'mts & ma',
                'mts dan ma',
                'mts/ma',
                'mts-ma',
            ]);

            return;
        }

        $levels = $activityLevel === 'mts'
            ? ['mts', 'madrasah tsanawiyah']
            : ['ma', 'aliyah', 'madrasah aliyah'];

        $query->whereIn(DB::raw('LOWER(TRIM(level))'), $levels);
    }

    private function normalizeLevel(?string $level): ?string
    {
        return match (mb_strtolower(trim((string) $level))) {
            'mts', 'madrasah tsanawiyah' => 'mts',
            'ma', 'aliyah', 'madrasah aliyah' => 'ma',
            'mts_ma', 'mts & ma', 'mts dan ma', 'mts/ma', 'mts-ma' => 'mts_ma',
            default => null,
        };
    }

    private function levelMatches(string $activityLevel, string $studentLevel): bool
    {
        return $activityLevel === 'mts_ma'
            ? in_array($studentLevel, ['mts', 'ma', 'mts_ma'], true)
            : $activityLevel === $studentLevel;
    }

    private function levelLabel(string $level): string
    {
        return match ($level) {
            'mts' => 'MTs',
            'ma' => 'MA',
            'mts_ma' => 'MTs & MA',
            default => '-',
        };
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless($institution->is_active && $institution->code === 'sekolah-pagi', 404);

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');
    }

    private function currentAcademicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            default => '-',
        };
    }

    private function error(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => $message,
        ], $status);
    }
}
