<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\SchoolExtracurricular;
use App\Models\SchoolExtracurricularAttendance;
use App\Models\SchoolExtracurricularAttendanceExcuse;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SchoolExtracurricularAttendanceReportService
{
    public function build(
        Institution $institution,
        SchoolExtracurricular $extracurricular,
        string $date,
        string $academicYear,
        ?string $className = null
    ): array {
        $enrollments = $this->eligibleEnrollmentQuery(
            $institution,
            $extracurricular,
            $academicYear
        )
            ->when($className, fn ($query) => $query->where('student_enrollments.class_name', $className))
            ->get();

        $studentIds = $enrollments->pluck('student_id');

        $attendances = $studentIds->isEmpty()
            ? collect()
            : SchoolExtracurricularAttendance::query()
                ->where('institution_id', $institution->id)
                ->where('school_extracurricular_id', $extracurricular->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('student_id', $studentIds)
                ->get()
                ->keyBy('student_id');

        $excuses = $studentIds->isEmpty()
            ? collect()
            : SchoolExtracurricularAttendanceExcuse::query()
                ->where('institution_id', $institution->id)
                ->where('school_extracurricular_id', $extracurricular->id)
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
            } elseif ($attendance) {
                $dayStatus = $attendance->status === 'terlambat'
                    ? 'terlambat'
                    : 'hadir';
                $notes = null;
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
                'level_label' => $this->levelLabel($enrollment->level),
                'class_name' => $enrollment->class_name ?: '-',
                'day_status' => $dayStatus,
                'day_status_label' => $this->dayStatusLabel($dayStatus),
                'scan_time' => $attendance?->scanned_at?->format('H:i:s'),
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

    public function classOptions(
        Institution $institution,
        SchoolExtracurricular $extracurricular,
        string $academicYear
    ): Collection {
        return $this->eligibleEnrollmentQuery($institution, $extracurricular, $academicYear)
            ->pluck('student_enrollments.class_name')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function eligibleEnrollmentQuery(
        Institution $institution,
        SchoolExtracurricular $extracurricular,
        string $academicYear
    ): Builder {
        $query = StudentEnrollment::query()
            ->with('student')
            ->join('students', 'students.id', '=', 'student_enrollments.student_id')
            ->where('student_enrollments.institution_id', $institution->id)
            ->where('student_enrollments.academic_year', $academicYear)
            ->where('student_enrollments.is_active', true)
            ->where('students.is_active', true)
            ->select('student_enrollments.*')
            ->orderBy('student_enrollments.level')
            ->orderBy('student_enrollments.class_name')
            ->orderBy('students.name');

        $this->applyLevelScope($query, $extracurricular->level);

        return $query;
    }

    private function applyLevelScope(Builder $query, string $activityLevel): void
    {
        if ($activityLevel === 'mts_ma') {
            $query->whereIn(DB::raw('LOWER(TRIM(student_enrollments.level))'), [
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

        $query->whereIn(DB::raw('LOWER(TRIM(student_enrollments.level))'), $levels);
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

    private function levelLabel(?string $level): string
    {
        return match (mb_strtolower(trim((string) $level))) {
            'mts', 'madrasah tsanawiyah' => 'MTs',
            'ma', 'aliyah', 'madrasah aliyah' => 'MA',
            'mts_ma', 'mts & ma', 'mts dan ma', 'mts/ma', 'mts-ma' => 'MTs & MA',
            default => '-',
        };
    }
}
