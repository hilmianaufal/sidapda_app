<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\SchoolAttendance;
use App\Models\SchoolAttendanceExcuse;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;

class SchoolAttendanceReportService
{
    public function build(
        Institution $institution,
        string $date,
        string $academicYear,
        ?string $level = null,
        ?string $className = null
    ): array {
        $enrollments = StudentEnrollment::query()
            ->with('student')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.institution_id', $institution->id)
            ->where('student_enrollments.academic_year', $academicYear)
            ->where('student_enrollments.is_active', true)
            ->where('students.is_active', true)
            ->when($level, fn ($query) => $query->where('student_enrollments.level', $level))
            ->when($className, fn ($query) => $query->where('student_enrollments.class_name', $className))
            ->select('student_enrollments.*')
            ->orderBy('student_enrollments.level')
            ->orderBy('student_enrollments.class_name')
            ->orderBy('students.name')
            ->get();

        $studentIds = $enrollments->pluck('student_id');

        $attendances = $studentIds->isEmpty()
            ? collect()
            : SchoolAttendance::query()
                ->where('institution_id', $institution->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

        $excuses = $studentIds->isEmpty()
            ? collect()
            : SchoolAttendanceExcuse::query()
                ->where('institution_id', $institution->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

        $rows = $enrollments->map(function (StudentEnrollment $enrollment) use ($attendances, $excuses) {
            $student = $enrollment->student;
            $attendance = $attendances->get($enrollment->student_id);
            $excuse = $excuses->get($enrollment->student_id);

            if ($excuse) {
                $dayStatus = $excuse->status;
                $notes = $excuse->notes;
            } elseif ($attendance?->check_in_at) {
                $dayStatus = $attendance->check_in_status === 'terlambat'
                    ? 'terlambat'
                    : 'hadir';
                $notes = null;
            } elseif ($attendance?->check_out_at) {
                $dayStatus = 'hadir';
                $notes = 'Scan masuk belum tercatat.';
            } else {
                $dayStatus = 'alpa';
                $notes = null;
            }

            return [
                'student_id' => $enrollment->student_id,
                'nis' => $student?->nis ?? '-',
                'name' => $student?->name ?? 'Siswa dihapus',
                'gender' => match ($student?->gender) {
                    'putra' => 'Putra',
                    'putri' => 'Putri',
                    default => '-',
                },
                'level' => $enrollment->level ?: '-',
                'class_name' => $enrollment->class_name ?: '-',
                'day_status' => $dayStatus,
                'day_status_label' => $this->dayStatusLabel($dayStatus),
                'check_in_time' => $attendance?->check_in_at?->format('H:i'),
                'check_in_status' => $attendance?->check_in_status,
                'check_in_status_label' => $this->checkInStatusLabel($attendance?->check_in_status),
                'check_out_time' => $attendance?->check_out_at?->format('H:i'),
                'check_out_status' => $attendance?->check_out_status,
                'check_out_status_label' => $this->checkOutStatusLabel($attendance?->check_out_status),
                'notes' => $notes ?: '-',
                'excuse_id' => $excuse?->id,
                'has_attachment' => (bool) $excuse?->attachment_path,
            ];
        })->values();

        return [
            'rows' => $rows,
            'summary' => $this->summary($rows),
        ];
    }

    private function summary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'hadir' => $rows->where('day_status', 'hadir')->count(),
            'terlambat' => $rows->where('day_status', 'terlambat')->count(),
            'izin' => $rows->where('day_status', 'izin')->count(),
            'sakit' => $rows->where('day_status', 'sakit')->count(),
            'lainnya' => $rows->where('day_status', 'lainnya')->count(),
            'alpa' => $rows->where('day_status', 'alpa')->count(),
            'sudah_pulang' => $rows->whereNotNull('check_out_time')->count(),
            'pulang_cepat' => $rows->where('check_out_status', 'pulang_cepat')->count(),
        ];
    }

    private function dayStatusLabel(string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'lainnya' => 'Lainnya',
            default => 'Alpa',
        };
    }

    private function checkInStatusLabel(?string $status): string
    {
        return match ($status) {
            'hadir' => 'Tepat Waktu',
            'terlambat' => 'Terlambat',
            default => '-',
        };
    }

    private function checkOutStatusLabel(?string $status): string
    {
        return match ($status) {
            'tepat_waktu' => 'Tepat Waktu',
            'pulang_cepat' => 'Pulang Cepat',
            default => '-',
        };
    }
}
