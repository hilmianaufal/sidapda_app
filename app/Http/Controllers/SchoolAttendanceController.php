<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolAttendance;
use App\Models\SchoolAttendanceExcuse;
use App\Models\SchoolAttendanceSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\StudentWhatsappNotifier;

class SchoolAttendanceController extends Controller
{
    public function index(Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $settings = SchoolAttendanceSetting::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->firstOrFail();

        $academicYear = $this->currentAcademicYear();
        $today = today()->toDateString();

        $enrollments = StudentEnrollment::query()
            ->where('institution_id', $institution->id)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->whereHas('student', fn ($query) => $query->where('is_active', true));

        $totalStudents = (clone $enrollments)->count();

        $todayQuery = SchoolAttendance::query()
            ->where('institution_id', $institution->id)
            ->whereDate('attendance_date', $today);

        $checkedIn = (clone $todayQuery)->whereNotNull('check_in_at')->count();
        $late = (clone $todayQuery)->where('check_in_status', 'terlambat')->count();
        $checkedOut = (clone $todayQuery)->whereNotNull('check_out_at')->count();
        $earlyLeave = (clone $todayQuery)->where('check_out_status', 'pulang_cepat')->count();

        $excuseQuery = SchoolAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->whereDate('attendance_date', $today);

        $excused = (clone $excuseQuery)->where('status', 'izin')->count();
        $sick = (clone $excuseQuery)->where('status', 'sakit')->count();
        $otherExcuses = (clone $excuseQuery)->where('status', 'lainnya')->count();

        $todayAttendances = (clone $todayQuery)
            ->with('student')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('school-attendance.index', [
            'institution' => $institution,
            'settings' => $settings,
            'academicYear' => $academicYear,
            'today' => $today,
            'totalStudents' => $totalStudents,
            'checkedIn' => $checkedIn,
            'notCheckedIn' => max(0, $totalStudents - $checkedIn - $excused - $sick - $otherExcuses),
            'late' => $late,
            'checkedOut' => $checkedOut,
            'earlyLeave' => $earlyLeave,
            'excused' => $excused,
            'sick' => $sick,
            'otherExcuses' => $otherExcuses,
            'todayAttendances' => $todayAttendances,
        ]);
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
            'action' => ['required', 'in:check_in,check_out'],
        ]);

        $settings = SchoolAttendanceSetting::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->first();

        if (!$settings) {
            return $this->error('Pengaturan absensi lembaga belum aktif.');
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

        $excuse = SchoolAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', today()->toDateString())
            ->first();

        if ($excuse) {
            return $this->error(
                $student->name.' sudah tercatat '.strtoupper($excuse->status).' hari ini dan tidak dapat melakukan scan.'
            );
        }

        $now = now();
        $currentTime = $now->format('H:i:s');
        $action = $data['action'];

        if ($action === 'check_in') {
            if ($currentTime < $settings->check_in_open) {
                return $this->error('Absen masuk belum dibuka. Mulai pukul '.$this->shortTime($settings->check_in_open).'.');
            }

            if ($currentTime > $settings->check_in_deadline) {
                return $this->error('Batas absen masuk pukul '.$this->shortTime($settings->check_in_deadline).' sudah lewat.');
            }

            $status = $currentTime > $settings->check_in_time ? 'terlambat' : 'hadir';
            $timeField = 'check_in_at';
            $statusField = 'check_in_status';
            $actionLabel = 'Absen Masuk';
        } else {
            if ($currentTime < $settings->check_out_open) {
                return $this->error('Absen pulang belum dibuka. Mulai pukul '.$this->shortTime($settings->check_out_open).'.');
            }

            if ($currentTime > $settings->check_out_deadline) {
                return $this->error('Batas absen pulang pukul '.$this->shortTime($settings->check_out_deadline).' sudah lewat.');
            }

            $status = $currentTime < $settings->check_out_time ? 'pulang_cepat' : 'tepat_waktu';
            $timeField = 'check_out_at';
            $statusField = 'check_out_status';
            $actionLabel = 'Absen Pulang';
        }

        [$attendance, $already] = DB::transaction(function () use (
            $institution,
            $student,
            $enrollment,
            $academicYear,
            $now,
            $timeField,
            $statusField,
            $status
        ) {
            $attendance = SchoolAttendance::query()
                ->where('institution_id', $institution->id)
                ->where('student_id', $student->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if (!$attendance) {
                $attendance = new SchoolAttendance([
                    'institution_id' => $institution->id,
                    'student_id' => $student->id,
                    'attendance_date' => $now->toDateString(),
                    'academic_year' => $academicYear,
                    'class_name_snapshot' => $enrollment->class_name,
                    'level_snapshot' => $enrollment->level,
                    'created_by' => auth()->id(),
                ]);
            }

            if ($attendance->{$timeField}) {
                return [$attendance, true];
            }

            $attendance->{$timeField} = $now;
            $attendance->{$statusField} = $status;
            $attendance->updated_by = auth()->id();
            $attendance->save();

            return [$attendance->fresh(), false];
        });

        $savedStatus = $attendance->{$statusField};
        $savedAt = $attendance->{$timeField};
        $statusLabel = $this->statusLabel($savedStatus);

        if (! $already && $savedAt) {
            $whatsapp->attendance(
                $institution,
                $student,
                $actionLabel,
                $institution->name,
                $savedStatus,
                $savedAt
            );
        }

        return response()->json([
            'ok' => true,
            'already' => $already,
            'message' => $already
                ? $student->name.' sudah melakukan '.strtolower($actionLabel).'.'
                : $actionLabel.' berhasil: '.$student->name.' ('.$statusLabel.').',
            'action' => $action,
            'action_label' => $actionLabel,
            'status' => $savedStatus,
            'status_label' => $statusLabel,
            'scanned_at' => $savedAt?->format('H:i:s'),
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'class_name' => $enrollment->class_name,
                'level' => $enrollment->level,
                'photo_url' => $student->photoUrl(),
            ],
        ]);
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless(
            $institution->is_active
                && in_array($institution->code, ['mi', 'mts', 'ma'], true),
            404
        );

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

    private function shortTime(string $time): string
    {
        return substr($time, 0, 5);
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'tepat_waktu' => 'Tepat Waktu',
            'pulang_cepat' => 'Pulang Cepat',
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
