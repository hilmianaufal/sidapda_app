<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\SchoolTeacher;
use App\Models\SchoolTeacherAttendance;
use App\Models\SchoolTeacherAttendanceExcuse;
use Illuminate\Support\Collection;

class SchoolTeacherAttendanceReportService
{
    public function build(
        Institution $institution,
        string $date,
        ?string $level = null
    ): array {
        $teachers = SchoolTeacher::query()
            ->where('institution_id', $institution->id)
            ->where('is_active', true)
            ->when($level, fn ($query) => $query->where('level', $level))
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $teacherIds = $teachers->pluck('id');

        $attendances = $teacherIds->isEmpty()
            ? collect()
            : SchoolTeacherAttendance::query()
                ->where('institution_id', $institution->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('school_teacher_id', $teacherIds)
                ->get()
                ->keyBy('school_teacher_id');

        $excuses = $teacherIds->isEmpty()
            ? collect()
            : SchoolTeacherAttendanceExcuse::query()
                ->where('institution_id', $institution->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('school_teacher_id', $teacherIds)
                ->get()
                ->keyBy('school_teacher_id');

        $rows = $teachers->map(function (SchoolTeacher $teacher) use ($attendances, $excuses) {
            $attendance = $attendances->get($teacher->id);
            $excuse = $excuses->get($teacher->id);

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
                'teacher_id' => $teacher->id,
                'teacher_code' => $teacher->teacher_code,
                'name' => $teacher->name,
                'gender' => $teacher->genderLabel(),
                'level' => $teacher->level,
                'level_label' => $teacher->levelLabel(),
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
