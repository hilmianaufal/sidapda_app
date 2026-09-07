<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolAttendanceSetting;
use App\Models\SchoolTeacher;
use App\Models\SchoolTeacherAttendance;
use App\Models\SchoolTeacherAttendanceExcuse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SchoolTeacherAttendanceController extends Controller
{
    public function index(Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $settings = SchoolAttendanceSetting::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->firstOrFail();

        $today = today()->toDateString();
        $totalTeachers = SchoolTeacher::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->count();

        $todayQuery = SchoolTeacherAttendance::query()
            ->where('institution_id', $institution->id)
            ->whereDate('attendance_date', $today);

        $checkedIn = (clone $todayQuery)->whereNotNull('check_in_at')->count();
        $late = (clone $todayQuery)->where('check_in_status', 'terlambat')->count();
        $checkedOut = (clone $todayQuery)->whereNotNull('check_out_at')->count();
        $earlyLeave = (clone $todayQuery)->where('check_out_status', 'pulang_cepat')->count();

        $excuseQuery = SchoolTeacherAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->whereDate('attendance_date', $today);

        $excused = (clone $excuseQuery)->where('status', 'izin')->count();
        $sick = (clone $excuseQuery)->where('status', 'sakit')->count();
        $otherExcuses = (clone $excuseQuery)->where('status', 'lainnya')->count();

        $todayAttendances = (clone $todayQuery)
            ->with('teacher')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('school-teacher-attendance.index', [
            'institution' => $institution,
            'settings' => $settings,
            'academicYear' => $this->currentAcademicYear(),
            'today' => $today,
            'totalTeachers' => $totalTeachers,
            'checkedIn' => $checkedIn,
            'notCheckedIn' => max(0, $totalTeachers - $checkedIn - $excused - $sick - $otherExcuses),
            'late' => $late,
            'checkedOut' => $checkedOut,
            'earlyLeave' => $earlyLeave,
            'excused' => $excused,
            'sick' => $sick,
            'otherExcuses' => $otherExcuses,
            'todayAttendances' => $todayAttendances,
        ]);
    }

    public function store(Request $request, Institution $institution): JsonResponse
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
        $teacher = SchoolTeacher::query()
            ->where('institution_id', $institution->id)
            ->where(function ($query) use ($token) {
                $query->where('qr_token', $token)
                    ->orWhere('teacher_code', $token);
            })
            ->first();

        if (!$teacher) {
            return $this->error('QR atau Kode Guru tidak dikenal.', 404);
        }

        if (!$teacher->is_active) {
            return $this->error('Guru sedang nonaktif. Hubungi admin.');
        }

        $excuse = SchoolTeacherAttendanceExcuse::query()
            ->where('institution_id', $institution->id)
            ->where('school_teacher_id', $teacher->id)
            ->whereDate('attendance_date', today()->toDateString())
            ->first();

        if ($excuse) {
            return $this->error(
                $teacher->name.' sudah tercatat '.strtoupper($excuse->status).' hari ini dan tidak dapat melakukan scan.'
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

        [$attendance, $state] = DB::transaction(function () use (
            $institution,
            $teacher,
            $now,
            $action,
            $timeField,
            $statusField,
            $status
        ) {
            $attendance = SchoolTeacherAttendance::query()
                ->where('institution_id', $institution->id)
                ->where('school_teacher_id', $teacher->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if ($action === 'check_out' && !$attendance?->check_in_at) {
                return [$attendance, 'missing_check_in'];
            }

            if (!$attendance) {
                $attendance = new SchoolTeacherAttendance([
                    'institution_id' => $institution->id,
                    'school_teacher_id' => $teacher->id,
                    'attendance_date' => $now->toDateString(),
                    'academic_year' => $this->currentAcademicYear(),
                    'teacher_code_snapshot' => $teacher->teacher_code,
                    'level_snapshot' => $teacher->level,
                    'created_by' => auth()->id(),
                ]);
            }

            if ($attendance->{$timeField}) {
                return [$attendance, 'already'];
            }

            $attendance->{$timeField} = $now;
            $attendance->{$statusField} = $status;
            $attendance->updated_by = auth()->id();
            $attendance->save();

            return [$attendance->fresh(), 'saved'];
        });

        if ($state === 'missing_check_in') {
            return $this->error($teacher->name.' belum melakukan absen masuk hari ini.');
        }

        $savedStatus = $attendance->{$statusField};
        $savedAt = $attendance->{$timeField};
        $statusLabel = $this->statusLabel($savedStatus);

        return response()->json([
            'ok' => true,
            'already' => $state === 'already',
            'message' => $state === 'already'
                ? $teacher->name.' sudah melakukan '.strtolower($actionLabel).'.'
                : $actionLabel.' berhasil: '.$teacher->name.' ('.$statusLabel.').',
            'action' => $action,
            'action_label' => $actionLabel,
            'status' => $savedStatus,
            'status_label' => $statusLabel,
            'scanned_at' => $savedAt?->format('H:i:s'),
            'teacher' => [
                'id' => $teacher->id,
                'teacher_code' => $teacher->teacher_code,
                'name' => $teacher->name,
                'level' => $teacher->levelLabel(),
                'photo_url' => asset('images/default.jpg'),
            ],
        ]);
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless(
            $institution->is_active
                && in_array($institution->code, ['mi', 'sekolah-pagi'], true),
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
