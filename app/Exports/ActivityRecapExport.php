<?php

namespace App\Exports;

use App\Models\Activity;
use App\Models\ActivityAttendance;
use App\Models\ActivitySession;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActivityRecapExport implements FromArray, WithHeadings
{
    private ?string $activityCategory = null;

    public function __construct(
        public string $date,
        public int $activityId,
        public string $activityName,
        public ?string $level = null,
        public ?string $kelas = null,
        public ?string $kamar = null,
    ) {}

    public function headings(): array
    {
        return [
            'Tanggal',
            'Kegiatan',
            'NIS',
            'Nama',
            'Jenjang/Kelas',
            'Kamar',
            'Status',
            'Jam Scan',
        ];
    }

    public function array(): array
    {
        $activity = Activity::findOrFail($this->activityId);
        $this->activityCategory = $activity->category;
        $academicYear = Student::academicYearForDate($this->date);
        $obligationAt = Carbon::parse($this->date.' '.$activity->start_time);
        $session = ActivitySession::where('activity_id', $this->activityId)
            ->whereDate('started_at', $this->date)
            ->first();

        $studentsQuery = Student::query()
            ->obligatedForActivity($activity->category, $academicYear, $obligationAt)
            ->when($this->kamar, fn ($q) => $q->where('kamar', $this->kamar));

        if ($activity->category === 'diniyah') {
            $studentsQuery->withInstitutionClass('madad', $academicYear);

            if ($this->level) {
                $studentsQuery->whereInstitutionLevel('madad', $academicYear, $this->level);
            }

            if ($this->kelas) {
                $studentsQuery->whereInstitutionClass('madad', $academicYear, $this->kelas);
            }
        } elseif ($this->kelas) {
            $studentsQuery->where('kelas', $this->kelas);
        }

        $students = (clone $studentsQuery)
            ->orderBy('name')
            ->get();

        $rows = [];

        if (!$session) {
            foreach ($students as $student) {
                $rows[] = $this->makeRow($student, null);
            }

            return $rows;
        }

        $attendances = ActivityAttendance::with(['student' => function ($student) use ($activity, $academicYear) {
                if ($activity->category === 'diniyah') {
                    $student->withInstitutionClass('madad', $academicYear);
                }
            }])
            ->where('activity_session_id', $session->id)
            ->whereHas('student', fn ($student) => $student
                ->obligatedForActivity($activity->category, $academicYear, $obligationAt))
            ->when($this->level || $this->kelas, function ($query) use ($activity, $academicYear) {
                $query->whereHas('student', function ($student) use ($activity, $academicYear) {
                    if ($activity->category === 'diniyah') {
                        if ($this->level) {
                            $student->whereInstitutionLevel('madad', $academicYear, $this->level);
                        }
                        if ($this->kelas) {
                            $student->whereInstitutionClass('madad', $academicYear, $this->kelas);
                        }
                    } elseif ($this->kelas) {
                        $student->where('kelas', $this->kelas);
                    }
                });
            })
            ->when($this->kamar, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kamar', $this->kamar)))
            ->get()
            ->keyBy('student_id');

        foreach ($students as $student) {
            $attendance = $attendances->get($student->id);

            $rows[] = $this->makeRow($student, $attendance);
        }

        return $rows;
    }

    private function makeRow(Student $student, ?ActivityAttendance $attendance): array
    {
        return [
            $this->date,
            $this->activityName,
            $student->nis,
            $student->name,
            $this->activityClass($student),
            $student->kamar ?? '-',
            $attendance ? $this->statusLabel($attendance->status) : 'Alpa',
            $attendance?->scanned_at?->format('H:i:s'),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'hadir' => 'Hadir',
            'terlambat' => 'Telat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'pulang' => 'Pulang',
            default => ucfirst((string) $status),
        };
    }

    private function activityClass(Student $student): string
    {
        return $this->activityCategory === 'diniyah'
            ? (($student->institution_level ?: 'Belum ada jenjang').' / Kelas '.($student->institution_class ?: '-'))
            : ($student->kelas ?? '-');
    }
}
