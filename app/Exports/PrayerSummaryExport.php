<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class PrayerSummaryExport implements FromArray, WithHeadings
{
    public function __construct(
        public string $period,
        public string $startDate,
        public string $endDate,
        public ?string $gender = null,
        public ?string $kelas = null,
        public ?string $kamar = null,
    ) {}

    public function headings(): array
    {
        return [
            'Periode',
            'NIS',
            'Nama',
            'Jenis Santri',
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
        $start = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->startOfDay();
        $end = $endDate->copy()->endOfDay();

        $prayers = Prayer::where('is_active', true)->orderBy('order')->get();

        $students = Student::query()->obligatedForPrayer()
            ->when($this->gender, fn ($q) => $q->where('gender', $this->gender))
            ->when($this->kelas, fn ($q) => $q->where('kelas', $this->kelas))
            ->when($this->kamar, fn ($q) => $q->where('kamar', $this->kamar))
            ->orderBy('name')
            ->get();

        $sessionIds = AttendanceSession::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])
            ->whereIn('prayer_id', $prayers->pluck('id'))
            ->pluck('id');

        $attendances = Attendance::whereIn('attendance_session_id', $sessionIds)->get();

        // Carbon 3 mengembalikan pecahan hari bila dibandingkan dengan endOfDay.
        // Bandingkan tanggal pada jam yang sama agar harian = 1 dan mingguan = 7.
        $days = (int) $start->diffInDays($endDate) + 1;
        $target = (int) $prayers->count() * $days;

        $rows = [];

        foreach ($students as $student) {
            $studentAttendances = $attendances->where('student_id', $student->id);

            $hadir = $studentAttendances->where('status', 'hadir')->count();
            $telat = $studentAttendances->where('status', 'terlambat')->count();
            $udzur = $studentAttendances->where('status', 'udzur')->count();
            $sakit = $studentAttendances->where('status', 'sakit')->count();
            $pulang = $studentAttendances->where('status', 'pulang')->count();

            $recorded = $hadir + $telat + $udzur + $sakit + $pulang;
            $alpa = max(0, $target - $recorded);

            $rows[] = [
                $start->format('d-m-Y') . ' s/d ' . $end->format('d-m-Y'),
                $student->nis,
                $student->name,
                $student->gender === 'putra' ? 'Putra' : ($student->gender === 'putri' ? 'Putri' : '-'),
                $student->kelas ?? '-',
                $student->kamar ?? '-',
                $hadir,
                $telat,
                $udzur,
                $sakit,
                $pulang,
                $alpa,
                $recorded . '/' . $target,
            ];
        }

        return $rows;
    }


}
