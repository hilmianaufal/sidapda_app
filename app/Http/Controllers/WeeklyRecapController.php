<?php

namespace App\Http\Controllers;

use App\Exports\WeeklyRecapExport;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use App\Support\ReportingWeek;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WeeklyRecapController extends Controller
{
    public function index(Request $request)
    {
        $week = $request->input('week', ReportingWeek::currentKey());
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');
        $prayerId = $request->input('prayer_id');

        [$start, $end] = ReportingWeek::fromKey($week);

        $prayers = Prayer::where('is_active', true)->orderBy('order')->get();
        $selectedPrayer = $prayerId
            ? Prayer::find($prayerId)
            : $prayers->first();

        $studentsQuery = Student::query()
            ->obligatedForPrayer()
            ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
            ->when($kamar, fn ($q) => $q->where('kamar', $kamar));

        $students = (clone $studentsQuery)->orderBy('name')->get();
        $totalStudents = $students->count();

        $sessionIds = collect();

        if ($selectedPrayer) {
            $sessionIds = AttendanceSession::where('prayer_id', $selectedPrayer->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('id');
        }

        $attendances = Attendance::with('student')
            ->whereIn('attendance_session_id', $sessionIds)
            ->whereHas('student', fn ($student) => $student->obligatedForPrayer())
            ->when($kelas, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kelas', $kelas)))
            ->when($kamar, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kamar', $kamar)))
            ->get();

        $hadirCount = $attendances->where('status', 'hadir')->count();
        $terlambatCount = $attendances->where('status', 'terlambat')->count();
        $udzurCount = $attendances->where('status', 'udzur')->count();
        $sakitCount = $attendances->where('status', 'sakit')->count();
        $pulangCount = $attendances->where('status', 'pulang')->count();

        $expected = $totalStudents * 7;
        $recorded = $hadirCount + $terlambatCount + $udzurCount + $sakitCount + $pulangCount;
        $alpaCount = max(0, $expected - $recorded);

        $kelasList = Student::query()->obligatedForPrayer()
            ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $kamarList = Student::query()->obligatedForPrayer()
            ->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

        return view('rekap.weekly', compact(
            'week',
            'start',
            'end',
            'kelas',
            'kamar',
            'prayerId',
            'prayers',
            'selectedPrayer',
            'kelasList',
            'kamarList',
            'students',
            'attendances',
            'totalStudents',
            'hadirCount',
            'terlambatCount',
            'udzurCount',
            'sakitCount',
            'pulangCount',
            'alpaCount',
            'expected'
        ));
    }


        public function exportExcel(Request $request)
    {
        $week = $request->input('week', ReportingWeek::currentKey());
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        $prayerId = (int) $request->input('prayer_id');

        if (!$prayerId) {
            $prayerId = Prayer::where('is_active', true)->orderBy('order')->value('id');
        }

        $filename = 'Rekap-Mingguan-' . $week . '.xlsx';

        return Excel::download(
            new WeeklyRecapExport($week, $prayerId, $kelas, $kamar),
            $filename
        );
    }
}
