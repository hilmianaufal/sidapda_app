<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use App\Support\ReportingWeek;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WeeklyRecapExport implements FromArray, WithHeadings
{
    public function __construct(
        public string $week,
        public int $prayerId,
        public ?string $kelas = null,
        public ?string $kamar = null,
    ) {}

    public function headings(): array
    {
        return [
            'Periode',
            'Sholat',
            'NIS',
            'Nama',
            'Jenjang',
            'Kamar',
            'Hadir',
            'Telat',
            'Udzur',
            'Sakit',
            'Pulang',
            'Alpa',
            'Total',
        ];
    }

    public function array(): array
    {
        [$start, $end] = ReportingWeek::fromKey($this->week);

        $prayer = Prayer::find($this->prayerId);

        $students = Student::query()
            ->obligatedForPrayer()
            ->when($this->kelas, fn ($q) => $q->where('kelas', $this->kelas))
            ->when($this->kamar, fn ($q) => $q->where('kamar', $this->kamar))
            ->orderBy('name')
            ->get();

        $sessionIds = AttendanceSession::where('prayer_id', $this->prayerId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('id');

        $attendances = Attendance::whereIn('attendance_session_id', $sessionIds)
            ->get();

        $rows = [];

        foreach ($students as $student) {
            $studentAttendances = $attendances->where('student_id', $student->id);

            $hadir = $studentAttendances->where('status', 'hadir')->count();
            $telat = $studentAttendances->where('status', 'terlambat')->count();
            $udzur = $studentAttendances->where('status', 'udzur')->count();
            $sakit = $studentAttendances->where('status', 'sakit')->count();
            $pulang = $studentAttendances->where('status', 'pulang')->count();

            $recorded = $hadir + $telat + $udzur + $sakit + $pulang;
            $alpa = max(0, 7 - $recorded);

            $rows[] = [
                $start->format('d-m-Y') . ' s/d ' . $end->format('d-m-Y'),
                $prayer?->name ?? '-',
                $student->nis,
                $student->name,
                $student->kelas ?? '-',
                $student->kamar ?? '-',
                $hadir,
                $telat,
                $udzur,
                $sakit,
                $pulang,
                $alpa,
                $recorded . '/7',
            ];
        }

        return $rows;
    }
}
