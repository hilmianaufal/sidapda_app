<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Prayer;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MonthlyRecapExport implements FromArray, WithHeadings
{
    public function __construct(
        public string $month,         // YYYY-MM
        public ?string $kelas = null,
        public ?string $kamar = null
    ) {}

    public function headings(): array
    {
        return [
            'Bulan',
            'NIS',
            'Nama',
            'Kelas',
            'Kamar',
            'Hadir',
            'Terlambat',
            'Total Scan',
            'Belum',
            'Persen',
        ];
    }

    public function array(): array
    {
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $this->month)->endOfMonth()->toDateString();
        $daysInMonth = Carbon::createFromFormat('Y-m', $this->month)->daysInMonth;

        $prayerCount = Prayer::where('is_active', true)->count();
        $expectedPerStudent = $daysInMonth * max(1, $prayerCount);

        // Base: santri aktif + filter
        $studentsQuery = Student::query()->obligatedForPrayer();
        if ($this->kelas) $studentsQuery->where('kelas', $this->kelas);
        if ($this->kamar) $studentsQuery->where('kamar', $this->kamar);

        // Aggregasi scan per santri di bulan tsb
        $stats = Attendance::query()
            ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
            ->whereHas('student', fn ($student) => $student->obligatedForPrayer())
            ->select([
                'attendances.student_id',
                DB::raw("SUM(CASE WHEN attendances.status='hadir' THEN 1 ELSE 0 END) as hadir"),
                DB::raw("SUM(CASE WHEN attendances.status='terlambat' THEN 1 ELSE 0 END) as terlambat"),
                DB::raw("COUNT(*) as total_scan"),
            ])
            ->whereBetween('attendance_sessions.date', [$start, $end])
            ->groupBy('attendances.student_id');

        $rows = $studentsQuery
            ->leftJoinSub($stats, 'st', fn($join) => $join->on('students.id', '=', 'st.student_id'))
            ->select([
                'students.nis',
                'students.name',
                'students.kelas',
                'students.kamar',
                DB::raw('COALESCE(st.hadir, 0) as hadir'),
                DB::raw('COALESCE(st.terlambat, 0) as terlambat'),
                DB::raw('COALESCE(st.total_scan, 0) as total_scan'),
            ])
            ->orderBy('students.name')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $scan = (int) $r->total_scan;
            $belum = max(0, $expectedPerStudent - $scan);
            $pct = $expectedPerStudent > 0 ? round(($scan / $expectedPerStudent) * 100) : 0;

            $out[] = [
                $this->month,
                $r->nis,
                $r->name,
                $r->kelas ?? '-',
                $r->kamar ?? '-',
                (int)$r->hadir,
                (int)$r->terlambat,
                $scan,
                $belum,
                $pct . '%',
            ];
        }

        return $out;
    }
}
