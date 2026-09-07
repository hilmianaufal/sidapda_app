<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Exports\MonthlyRecapExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class MonthlyRecapController extends Controller
{
    public function index(Request $request)
    {
        // bulan default: bulan ini (format YYYY-MM)
        $month = $request->input('month', now()->format('Y-m'));
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        // range tanggal bulan tsb
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $daysInMonth = Carbon::createFromFormat('Y-m', $month)->daysInMonth;

        // sholat aktif (untuk menghitung total seharusnya)
        $prayerCount = Prayer::where('is_active', true)->count();
        $expectedPerStudent = $daysInMonth * max(1, $prayerCount); // minimal 1 biar aman

        // filter dropdown
        $kelasList = Student::query()->obligatedForPrayer()
            ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $kamarList = Student::query()->obligatedForPrayer()
            ->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

        // Query santri aktif
        $studentsQuery = Student::query()->obligatedForPrayer();
        if ($kelas) $studentsQuery->where('kelas', $kelas);
        if ($kamar) $studentsQuery->where('kamar', $kamar);

        // Ambil agregasi absensi per santri untuk range bulan
        // join: attendances -> attendance_sessions (date filter)
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

        // ambil list santri + join stats (left join karena ada yang 0 scan)
        $rows = $studentsQuery
            ->leftJoinSub($stats, 'st', function ($join) {
                $join->on('students.id', '=', 'st.student_id');
            })
            ->select([
                'students.id',
                'students.nis',
                'students.name',
                'students.kelas',
                'students.kamar',
                DB::raw('COALESCE(st.hadir, 0) as hadir'),
                DB::raw('COALESCE(st.terlambat, 0) as terlambat'),
                DB::raw('COALESCE(st.total_scan, 0) as total_scan'),
            ])
            ->orderBy('students.name')
            ->paginate(20)
            ->withQueryString();

        // hitung summary global
        $totalStudents = (clone $studentsQuery)->count();

        // totals dari stats untuk semua student filter
        $global = (clone $stats)
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->when($kelas, fn($q) => $q->where('students.kelas', $kelas))
            ->when($kamar, fn($q) => $q->where('students.kamar', $kamar))
            ->first();

        $globalHadir = (int)($global->hadir ?? 0);
        $globalTelat = (int)($global->terlambat ?? 0);
        $globalScan  = (int)($global->total_scan ?? 0);
        $globalExpected = $totalStudents * $expectedPerStudent;
        $globalBelum = max(0, $globalExpected - $globalScan);

        return view('rekap.monthly', compact(
            'month', 'kelas', 'kamar',
            'start', 'end', 'daysInMonth',
            'prayerCount', 'expectedPerStudent',
            'kelasList', 'kamarList',
            'rows',
            'totalStudents',
            'globalHadir', 'globalTelat', 'globalScan', 'globalExpected', 'globalBelum'
        ));
    }

    public function exportExcel(Request $request)
{
    $month = $request->input('month', now()->format('Y-m'));
    $kelas = $request->input('kelas');
    $kamar = $request->input('kamar');

    $filename = 'Rekap-Bulanan-'.$month
        .($kelas ? '-Kelas-'.$kelas : '')
        .($kamar ? '-Kamar-'.$kamar : '')
        .'.xlsx';

    return Excel::download(new MonthlyRecapExport($month, $kelas, $kamar), $filename);
}

public function exportPdf(Request $request)
{
    $month = $request->input('month', now()->format('Y-m'));
    $kelas = $request->input('kelas');
    $kamar = $request->input('kamar');

    $start = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
    $end   = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
    $daysInMonth = \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->daysInMonth;

    $prayerCount = \App\Models\Prayer::where('is_active', true)->count();
    $expectedPerStudent = $daysInMonth * max(1, $prayerCount);

    // Ambil data sama seperti halaman (tanpa paginate biar full export)
    $studentsQuery = \App\Models\Student::query()->obligatedForPrayer();
    if ($kelas) $studentsQuery->where('kelas', $kelas);
    if ($kamar) $studentsQuery->where('kamar', $kamar);

    $stats = \App\Models\Attendance::query()
        ->join('attendance_sessions', 'attendance_sessions.id', '=', 'attendances.attendance_session_id')
        ->whereHas('student', fn ($student) => $student->obligatedForPrayer())
        ->select([
            'attendances.student_id',
            \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN attendances.status='hadir' THEN 1 ELSE 0 END) as hadir"),
            \Illuminate\Support\Facades\DB::raw("SUM(CASE WHEN attendances.status='terlambat' THEN 1 ELSE 0 END) as terlambat"),
            \Illuminate\Support\Facades\DB::raw("COUNT(*) as total_scan"),
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
            \Illuminate\Support\Facades\DB::raw('COALESCE(st.hadir, 0) as hadir'),
            \Illuminate\Support\Facades\DB::raw('COALESCE(st.terlambat, 0) as terlambat'),
            \Illuminate\Support\Facades\DB::raw('COALESCE(st.total_scan, 0) as total_scan'),
        ])
        ->orderBy('students.name')
        ->get();

    // Summary global
    $totalStudents = (clone $studentsQuery)->count();
    $globalExpected = $totalStudents * $expectedPerStudent;

    $globalHadir = 0; $globalTelat = 0; $globalScan = 0;
    foreach ($rows as $r) {
        $globalHadir += (int)$r->hadir;
        $globalTelat += (int)$r->terlambat;
        $globalScan  += (int)$r->total_scan;
    }
    $globalBelum = max(0, $globalExpected - $globalScan);

    $pdf = Pdf::loadView('rekap.monthly_pdf', compact(
        'month','kelas','kamar','start','end','daysInMonth',
        'prayerCount','expectedPerStudent',
        'rows',
        'totalStudents','globalExpected','globalHadir','globalTelat','globalScan','globalBelum'
    ))->setPaper('A4', 'portrait');

    $filename = 'Rekap-Bulanan-'.$month
        .($kelas ? '-Kelas-'.$kelas : '')
        .($kamar ? '-Kamar-'.$kamar : '')
        .'.pdf';

    return $pdf->download($filename);
}

}
