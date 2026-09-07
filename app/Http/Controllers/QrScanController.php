<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\AttendanceSession;
use App\Services\PrayerTimeService;
use App\Services\AttendanceSessionService;
use App\Services\StudentWhatsappNotifier;

class QrScanController extends Controller
{
    public function index(PrayerTimeService $prayerService)
    {
        $activePrayer = $prayerService->getActivePrayer();


        return view('scan.index', compact('activePrayer'));
    }

   public function store(
        Request $request,
        PrayerTimeService $prayerService,
        AttendanceSessionService $sessionService,
        StudentWhatsappNotifier $whatsapp
    )
    {
        $data = $request->validate([
            'token' => ['required','string'],
        ]);

        // 1) Pastikan ada sholat aktif
        $activePrayer = $prayerService->getActivePrayer();
        if (!$activePrayer) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada sholat aktif saat ini.',
            ], 422);
        }

        // 2) Cari santri dari token
        $student = Student::where('qr_token', $data['token'])->first();
        if (!$student) {
            return response()->json([
                'ok' => false,
                'message' => 'QR tidak dikenal / santri tidak ditemukan.',
            ], 404);
        }

        if (!$student->is_active) {
            return response()->json([
                'ok' => false,
                'message' => 'Santri nonaktif. Hubungi admin.',
            ], 422);
        }

        if (! $student->isObligatedForPrayer()) {
            $message = $student->isAwayFromBoarding()
                ? 'Santri sedang berstatus pulang. Scan kembali ke Pondok terlebih dahulu.'
                : 'Santri tidak mukim tidak memiliki kewajiban absensi salat Pondok.';

            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 422);
        }

        // 3) Ambil / buat session hari ini untuk sholat aktif
        $date = Carbon::today()->toDateString();

        $session = $sessionService->getOrCreateTodaySession($activePrayer);

        // kalau session sudah closed, tolak scan
        if ($session->status === 'closed') {
            return response()->json([
                'ok' => false,
                'message' => 'Sesi '.$activePrayer->name.' sudah ditutup.',
            ], 422);
        }

        // 4) Cegah scan dobel
        $existing = Attendance::where('attendance_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => true,
                'already' => true,
                'message' => 'Sudah absen untuk '.$activePrayer->name.'.',
                'prayer' => $activePrayer->name,
                'status' => $existing->status,
                'scanned_at' => $existing->scanned_at->format('H:i:s'),
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'name' => $student->name,
                    'kelas' => $student->kelas,
                    'kamar' => $student->kamar,
                    'photo_url' => $student->photoUrl(),
                ]
            ]);
        }

        // 5) Tentukan status hadir/terlambat
        $status = $prayerService->isLate($activePrayer) ? 'terlambat' : 'hadir';

        $attendance = Attendance::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'scanned_at' => now(),
            'status' => $status,
        ]);

        $institution = Institution::query()->where('code', 'ponpes')->first();
        if ($institution) {
            $whatsapp->attendance(
                $institution,
                $student,
                'Salat',
                $activePrayer->name,
                $status,
                $attendance->scanned_at
            );
        }

        return response()->json([
            'ok' => true,
            'already' => false,
            'message' => 'Berhasil absen '.$activePrayer->name.' ('.$status.').',
            'prayer' => $activePrayer->name,
            'status' => $status,
            'scanned_at' => $attendance->scanned_at->format('H:i:s'),
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'kelas' => $student->kelas,
                'kamar' => $student->kamar,
                'photo_url' => $student->photoUrl(),
            ],
        ]);
    }
}
