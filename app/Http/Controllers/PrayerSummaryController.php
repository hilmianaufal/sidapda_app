<?php

namespace App\Http\Controllers;

use App\Exports\PrayerSummaryExport;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use App\Support\ReportingWeek;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PrayerSummaryController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $gender = $request->input('gender');
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        $prayers = Prayer::where('is_active', true)
            ->orderBy('order')
            ->get();

        $students = Student::query()
            ->obligatedForPrayer()
            ->when($gender, fn ($q) => $q->where('gender', $gender))
            ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
            ->when($kamar, fn ($q) => $q->where('kamar', $kamar))
            ->orderBy('name')
            ->get();

        $sessionIds = AttendanceSession::whereDate('date', $date)
            ->whereIn('prayer_id', $prayers->pluck('id'))
            ->pluck('id');

        $attendances = Attendance::whereIn('attendance_session_id', $sessionIds)
            ->get();

        $totalPrayer = $prayers->count();
        $totalTarget = $students->count() * $totalPrayer;

        $rows = $students->map(function ($student) use ($attendances, $totalPrayer) {
            $studentAttendances = $attendances->where('student_id', $student->id);

            $hadir = $studentAttendances->where('status', 'hadir')->count();
            $telat = $studentAttendances->where('status', 'terlambat')->count();
            $udzur = $studentAttendances->where('status', 'udzur')->count();
            $sakit = $studentAttendances->where('status', 'sakit')->count();
            $pulang = $studentAttendances->where('status', 'pulang')->count();

            $recorded = $hadir + $telat + $udzur + $sakit + $pulang;
            $alpa = max(0, $totalPrayer - $recorded);

            return [
                'student' => $student,
                'hadir' => $hadir,
                'telat' => $telat,
                'udzur' => $udzur,
                'sakit' => $sakit,
                'pulang' => $pulang,
                'alpa' => $alpa,
                'total' => $recorded,
                'target' => $totalPrayer,
            ];
        });

        $summary = [
            'total_santri' => $students->count(),
            'total_target' => $totalTarget,
            'hadir' => $rows->sum('hadir'),
            'telat' => $rows->sum('telat'),
            'udzur' => $rows->sum('udzur'),
            'sakit' => $rows->sum('sakit'),
            'pulang' => $rows->sum('pulang'),
            'alpa' => $rows->sum('alpa'),
        ];

        $kelasList = Student::query()->obligatedForPrayer()
            ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $kamarList = Student::query()->obligatedForPrayer()
            ->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

        return view('rekap.salat.daily', compact(
            'date',
            'gender',
            'kelas',
            'kamar',
            'prayers',
            'rows',
            'summary',
            'kelasList',
            'kamarList'
        ));
    }

    public function weekly(Request $request)
{
    $week = $request->input('week', ReportingWeek::currentKey());
    $gender = $request->input('gender');
    $kelas = $request->input('kelas');
    $kamar = $request->input('kamar');

    [$start, $end] = ReportingWeek::fromKey($week);

    $prayers = Prayer::where('is_active', true)->orderBy('order')->get();

    $students = Student::query()
        ->obligatedForPrayer()
        ->when($gender, fn ($q) => $q->where('gender', $gender))
        ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
        ->when($kamar, fn ($q) => $q->where('kamar', $kamar))
        ->orderBy('name')
        ->get();

    $sessionIds = AttendanceSession::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])
        ->whereIn('prayer_id', $prayers->pluck('id'))
        ->pluck('id');

    $attendances = Attendance::whereIn('attendance_session_id', $sessionIds)->get();

    $totalPrayer = $prayers->count() * 7;
    $totalTarget = $students->count() * $totalPrayer;

    $rows = $students->map(function ($student) use ($attendances, $totalPrayer) {
        $studentAttendances = $attendances->where('student_id', $student->id);

        $hadir = $studentAttendances->where('status', 'hadir')->count();
        $telat = $studentAttendances->where('status', 'terlambat')->count();
        $udzur = $studentAttendances->where('status', 'udzur')->count();
        $sakit = $studentAttendances->where('status', 'sakit')->count();
        $pulang = $studentAttendances->where('status', 'pulang')->count();

        $recorded = $hadir + $telat + $udzur + $sakit + $pulang;
        $alpa = max(0, $totalPrayer - $recorded);

        return [
            'student' => $student,
            'hadir' => $hadir,
            'telat' => $telat,
            'udzur' => $udzur,
            'sakit' => $sakit,
            'pulang' => $pulang,
            'alpa' => $alpa,
            'total' => $recorded,
            'target' => $totalPrayer,
        ];
    });

    $summary = [
        'total_santri' => $students->count(),
        'total_target' => $totalTarget,
        'hadir' => $rows->sum('hadir'),
        'telat' => $rows->sum('telat'),
        'udzur' => $rows->sum('udzur'),
        'sakit' => $rows->sum('sakit'),
        'pulang' => $rows->sum('pulang'),
        'alpa' => $rows->sum('alpa'),
    ];

    $kelasList = Student::query()->obligatedForPrayer()
        ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
    $kamarList = Student::query()->obligatedForPrayer()
        ->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

    return view('rekap.salat.weekly', compact(
        'week',
        'start',
        'end',
        'gender',
        'kelas',
        'kamar',
        'prayers',
        'rows',
        'summary',
        'kelasList',
        'kamarList'
    ));
}

public function monthly(Request $request)
{
    $month = (int) $request->input('month', now()->month);
    $year = (int) $request->input('year', now()->year);

    $gender = $request->input('gender');
    $kelas = $request->input('kelas');
    $kamar = $request->input('kamar');

    $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
    $end = $start->copy()->endOfMonth();

    $daysInMonth = $start->daysInMonth;

    $prayers = Prayer::where('is_active', true)
        ->orderBy('order')
        ->get();

    $students = Student::query()
        ->obligatedForPrayer()
        ->when($gender, fn ($q) => $q->where('gender', $gender))
        ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
        ->when($kamar, fn ($q) => $q->where('kamar', $kamar))
        ->orderBy('name')
        ->get();

    $sessionIds = AttendanceSession::whereBetween('date', [
            $start->toDateString(),
            $end->toDateString(),
        ])
        ->whereIn('prayer_id', $prayers->pluck('id'))
        ->pluck('id');

    $attendances = Attendance::whereIn('attendance_session_id', $sessionIds)->get();

    $totalPrayer = $prayers->count() * $daysInMonth;
    $totalTarget = $students->count() * $totalPrayer;

    $rows = $students->map(function ($student) use ($attendances, $totalPrayer) {
        $studentAttendances = $attendances->where('student_id', $student->id);

        $hadir = $studentAttendances->where('status', 'hadir')->count();
        $telat = $studentAttendances->where('status', 'terlambat')->count();
        $udzur = $studentAttendances->where('status', 'udzur')->count();
        $sakit = $studentAttendances->where('status', 'sakit')->count();
        $pulang = $studentAttendances->where('status', 'pulang')->count();

        $recorded = $hadir + $telat + $udzur + $sakit + $pulang;
        $alpa = max(0, $totalPrayer - $recorded);

        return [
            'student' => $student,
            'hadir' => $hadir,
            'telat' => $telat,
            'udzur' => $udzur,
            'sakit' => $sakit,
            'pulang' => $pulang,
            'alpa' => $alpa,
            'total' => $recorded,
            'target' => $totalPrayer,
        ];
    });

    $summary = [
        'total_santri' => $students->count(),
        'total_target' => $totalTarget,
        'hadir' => $rows->sum('hadir'),
        'telat' => $rows->sum('telat'),
        'udzur' => $rows->sum('udzur'),
        'sakit' => $rows->sum('sakit'),
        'pulang' => $rows->sum('pulang'),
        'alpa' => $rows->sum('alpa'),
    ];

    $kelasList = Student::query()->obligatedForPrayer()
        ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
    $kamarList = Student::query()->obligatedForPrayer()
        ->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

    return view('rekap.salat.monthly', compact(
        'month',
        'year',
        'start',
        'end',
        'gender',
        'kelas',
        'kamar',
        'prayers',
        'rows',
        'summary',
        'kelasList',
        'kamarList'
    ));
}

    public function exportExcel(Request $request, string $period)
{
    $gender = $request->input('gender');
    $kelas = $request->input('kelas');
    $kamar = $request->input('kamar');

    if ($period === 'daily') {
        $start = \Carbon\Carbon::parse($request->input('date', now()->toDateString()));
        $end = $start->copy();
    } elseif ($period === 'weekly') {
        $week = $request->input('week', ReportingWeek::currentKey());
        [$start, $end] = ReportingWeek::fromKey($week);
    } else {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
    }

    return Excel::download(
        new PrayerSummaryExport(
            $period,
            $start->toDateString(),
            $end->toDateString(),
            $gender,
            $kelas,
            $kamar
        ),
        'Rekap-Salat-'.$period.'-'.$start->format('Y-m-d').'.xlsx'
    );
}
}
