<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RekapAbsensiExport implements FromArray, WithHeadings
{
    public function __construct(
        public string $date,
        public int $prayerId,
        public ?string $kelas,
        public ?string $kamar,
        public string $prayerName
    ) {}

    public function headings(): array
    {
        return [
            'Tanggal',
            'Sholat',
            'NIS',
            'Nama',
            'Kelas',
            'Kamar',
            'Status',
            'Jam Scan',
        ];
    }

    public function array(): array
    {
        $session = AttendanceSession::firstOrCreate(
            ['date' => $this->date, 'prayer_id' => $this->prayerId],
            ['status' => 'live']
        );
        $prayer = Prayer::findOrFail($this->prayerId);
        $obligationAt = Carbon::parse($this->date.' '.$prayer->start_time);

        $attQuery = Attendance::query()
            ->with('student')
            ->where('attendance_session_id', $session->id)
            ->whereHas('student', fn ($student) => $student->obligatedForPrayer($obligationAt))
            ->when($this->kelas, fn($q) => $q->whereHas('student', fn($s) => $s->where('kelas', $this->kelas)))
            ->when($this->kamar, fn($q) => $q->whereHas('student', fn($s) => $s->where('kamar', $this->kamar)))
            ->orderBy('scanned_at');

        $rows = [];

        foreach ($attQuery->get() as $a) {
            $rows[] = [
                $this->date,
                $this->prayerName,
                $a->student->nis,
                $a->student->name,
                $a->student->kelas ?? '-',
                $a->student->kamar ?? '-',
                $a->status,
                optional($a->scanned_at)->format('H:i:s'),
            ];
        }

        // OPTIONAL: tambahkan santri yang belum absen di bawah
        $studentsQuery = Student::query()->obligatedForPrayer($obligationAt)
            ->when($this->kelas, fn($q) => $q->where('kelas', $this->kelas))
            ->when($this->kamar, fn($q) => $q->where('kamar', $this->kamar));

        $presentIds = Attendance::where('attendance_session_id', $session->id)
            ->whereHas('student', fn ($student) => $student->obligatedForPrayer($obligationAt))
            ->when($this->kelas || $this->kamar, function ($q) {
                $q->whereHas('student');
            })
            ->pluck('student_id');

        foreach ($studentsQuery->whereNotIn('id', $presentIds)->orderBy('name')->get() as $s) {
            $rows[] = [
                $this->date,
                $this->prayerName,
                $s->nis,
                $s->name,
                $s->kelas ?? '-',
                $s->kamar ?? '-',
                'belum',
                null,
            ];
        }

        return $rows;
    }
}
