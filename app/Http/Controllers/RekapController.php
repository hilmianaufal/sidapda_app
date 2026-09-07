<?php

namespace App\Http\Controllers;
use App\Exports\RekapAbsensiExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Prayer;
use App\Models\Student;
use App\Services\PrayerTimeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RekapController extends Controller
{
    public function index(Request $request, PrayerTimeService $prayerService)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $groupKelas = $request->input('kelas'); // string
        $groupKamar = $request->input('kamar'); // string
        $gender = $request->input('gender');
        $prayers = Prayer::where('is_active', true)->orderBy('order')->get();

        // default: sholat aktif jika ada, kalau tidak: prayer pertama
        $defaultPrayer = $prayerService->getActivePrayer() ?: $prayers->first();
        $prayerId = (int)($request->input('prayer_id', $defaultPrayer?->id));

        $selectedPrayer = $prayers->firstWhere('id', $prayerId) ?: $defaultPrayer;
        $obligationAt = Carbon::parse(
            $date.' '.($selectedPrayer?->start_time ?? '23:59:59')
        );

        // Total santri (bisa difilter)
        $studentsQuery = Student::query()->obligatedForPrayer($obligationAt);
        if ($groupKelas) $studentsQuery->where('kelas', $groupKelas);
        if ($groupKamar) $studentsQuery->where('kamar', $groupKamar);
        $totalStudents = (clone $studentsQuery)->count();

        // Session untuk tanggal+sholat
        $session = null;
        if ($selectedPrayer) {
            $session = AttendanceSession::firstOrCreate(
                ['date' => $date, 'prayer_id' => $selectedPrayer->id],
                ['status' => 'live']
            );
        }

        // Attendance yang sudah scan
        $attQuery = Attendance::query()
            ->with(['student'])
            ->when($session, fn($q) => $q->where('attendance_session_id', $session->id))
            ->whereHas('student', fn ($student) => $student->obligatedForPrayer($obligationAt))
            ->when($groupKelas, fn($q) => $q->whereHas('student', fn($s) => $s->where('kelas', $groupKelas)))
            ->when($groupKamar, fn($q) => $q->whereHas('student', fn($s) => $s->where('kamar', $groupKamar)))
            ->when($gender, fn ($q) => $q->whereHas('student', fn ($student) => $student->where('gender', $gender)));
        $hadirCount = (clone $attQuery)->where('status', 'hadir')->count();
        $terlambatCount = (clone $attQuery)->where('status', 'terlambat')->count();
        $sudahCount = $hadirCount + $terlambatCount;
        $belumCount = max(0, $totalStudents - $sudahCount);

        $attendances = (clone $attQuery)
            ->orderByDesc('scanned_at')
            ->paginate(15)
            ->withQueryString();

        // List belum absen (ambil ID student yang sudah absen, lalu query student sisanya)
        $absentStudents = collect();
        if ($session) {
            $presentIds = Attendance::where('attendance_session_id', $session->id)
                ->whereHas('student', fn ($student) => $student->obligatedForPrayer($obligationAt))
                ->when($groupKelas || $groupKamar, function ($q) use ($groupKelas, $groupKamar) {
                    $q->whereHas('student', function ($s) use ($groupKelas, $groupKamar) {
                        if ($groupKelas) $s->where('kelas', $groupKelas);
                        if ($groupKamar) $s->where('kamar', $groupKamar);
                    });
                })
                ->pluck('student_id');

            $absentStudents = (clone $studentsQuery)
                ->whereNotIn('id', $presentIds)
                ->orderBy('name')
                ->get();
        }

        // dropdown filter kelas/kamar dari DB
        $kelasList = Student::query()->obligatedForPrayer($obligationAt)
            ->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas');
        $kamarList = Student::query()->obligatedForPrayer($obligationAt)
            ->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar');

        $sakitCount = (clone $attQuery)->where('status', 'sakit')->count();
        $udzurCount = (clone $attQuery)->where('status', 'udzur')->count();
        $pulangCount = (clone $attQuery)->where('status', 'pulang')->count();

        return view('rekap.index', compact(
            'date',
            'prayers',
            'selectedPrayer',
            'prayerId',
            'kelasList',
            'gender',
            'kamarList',
            'groupKelas',
            'groupKamar',
            'totalStudents',
            'hadirCount',
            'terlambatCount',
            'belumCount',
            'attendances',
            'absentStudents',
            'sakitCount',
            'udzurCount',
            'pulangCount'
        ));
    }

    public function exportExcel(Request $request, PrayerTimeService $prayerService)
    {
        $date = $request->input('date', now()->toDateString());
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');

        $prayers = \App\Models\Prayer::where('is_active', true)->orderBy('order')->get();
        $defaultPrayer = $prayerService->getActivePrayer() ?: $prayers->first();
        $prayerId = (int)($request->input('prayer_id', $defaultPrayer?->id));
        $selectedPrayer = $prayers->firstWhere('id', $prayerId) ?: $defaultPrayer;

        if (!$selectedPrayer) abort(404, 'Sholat tidak ditemukan.');

        $filename = 'Rekap-'.$selectedPrayer->name.'-'.$date.'.xlsx';

        return Excel::download(
            new RekapAbsensiExport($date, $selectedPrayer->id, $kelas, $kamar, $selectedPrayer->name),
            $filename
        );
    }

    public function exportPdf(Request $request, PrayerTimeService $prayerService)
{
    $date = $request->input('date', now()->toDateString());
    $kelas = $request->input('kelas');
    $kamar = $request->input('kamar');

    $prayers = Prayer::where('is_active', true)->orderBy('order')->get();
    $defaultPrayer = $prayerService->getActivePrayer() ?: $prayers->first();

    $prayerId = (int) ($request->input('prayer_id', $defaultPrayer?->id));
    $selectedPrayer = $prayers->firstWhere('id', $prayerId) ?: $defaultPrayer;

    if (!$selectedPrayer) {
        abort(404, 'Sholat tidak ditemukan.');
    }

    $obligationAt = Carbon::parse($date.' '.$selectedPrayer->start_time);

    $session = AttendanceSession::firstOrCreate(
        [
            'date' => $date,
            'prayer_id' => $selectedPrayer->id,
        ],
        [
            'status' => 'live',
        ]
    );

    $students = Student::query()->obligatedForPrayer($obligationAt)
        ->when($kelas, fn ($q) => $q->where('kelas', $kelas))
        ->when($kamar, fn ($q) => $q->where('kamar', $kamar))
        ->orderBy('name')
        ->get();

    $attendances = Attendance::with('student')
        ->where('attendance_session_id', $session->id)
        ->whereHas('student', fn ($student) => $student->obligatedForPrayer($obligationAt))
        ->when($kelas, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kelas', $kelas)))
        ->when($kamar, fn ($q) => $q->whereHas('student', fn ($s) => $s->where('kamar', $kamar)))
        ->get();

    $attendancesByStudent = $attendances->keyBy('student_id');

    $totalStudents = $students->count();

    $hadirCount = $attendances->where('status', 'hadir')->count();
    $terlambatCount = $attendances->where('status', 'terlambat')->count();
    $udzurCount = $attendances->where('status', 'udzur')->count();
    $sakitCount = $attendances->where('status', 'sakit')->count();
    $pulangCount = $attendances->where('status', 'pulang')->count();

    $sudahCount = $hadirCount + $terlambatCount + $udzurCount + $sakitCount + $pulangCount;
    $belumCount = max(0, $totalStudents - $sudahCount);

    $pdf = Pdf::loadView('rekap.pdf', compact(
        'date',
        'kelas',
        'kamar',
        'selectedPrayer',
        'students',
        'attendances',
        'attendancesByStudent',
        'totalStudents',
        'hadirCount',
        'terlambatCount',
        'udzurCount',
        'sakitCount',
        'pulangCount',
        'belumCount'
    ))->setPaper('A4', 'portrait');

    $filename = 'Rekap-' . $selectedPrayer->name . '-' . $date . '.pdf';

    return $pdf->download($filename);
}


        public function markStatus(Request $request)
        {
            $data = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'prayer_id' => ['required', 'exists:prayers,id'],
                'date' => ['required', 'date'],
                'status' => ['required', 'in:udzur,sakit,pulang'],
            ]);

            $session = AttendanceSession::firstOrCreate(
                [
                    'date' => $data['date'],
                    'prayer_id' => $data['prayer_id'],
                ],
                [
                    'status' => 'live',
                ]
            );

            $student = Student::findOrFail($data['student_id']);
            $prayer = Prayer::findOrFail($data['prayer_id']);
            $obligationAt = Carbon::parse($data['date'].' '.$prayer->start_time);

            if (! $student->isObligatedForPrayer($obligationAt)) {
                $message = $student->isAwayFromBoarding($obligationAt)
                    ? 'Santri sedang berstatus pulang pada waktu absensi ini.'
                    : 'Santri tidak mukim tidak memiliki kewajiban absensi salat Pondok.';

                return back()->with('error', $message);
            }

            Attendance::updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $data['student_id'],
                ],
                [
                    'scanned_at' => now(),
                    'status' => $data['status'],
                ]
            );

            return back()->with('success', 'Status berhasil diperbarui.');
        }


        public function cancelStatus(Request $request)
        {
            $data = $request->validate([
                'attendance_id' => ['required', 'exists:attendances,id'],
            ]);

            $attendance = Attendance::findOrFail($data['attendance_id']);

            if (in_array($attendance->status, ['udzur', 'sakit', 'pulang'])) {
                $attendance->delete();

                return back()->with('success', 'Status manual berhasil dibatalkan.');
            }

            return back()->with('error', 'Absensi scan tidak bisa dibatalkan dari sini.');
        }




}
