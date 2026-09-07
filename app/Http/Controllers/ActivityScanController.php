<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Institution;
use App\Models\ActivityAttendance;
use App\Services\ActivityTimeService;
use App\Services\ActivitySessionService;
use App\Services\StudentWhatsappNotifier;
use Illuminate\Http\Request;

class ActivityScanController extends Controller
{
    public function index(Request $request, ActivityTimeService $activityService)
    {
        $category = $this->categoryFromRequest($request);
        $activeActivity = $activityService->getActiveActivity($category);

        return view('activities.scan', compact('activeActivity', 'category'));
    }

    public function store(
        Request $request,
        ActivityTimeService $activityService,
        ActivitySessionService $sessionService,
        StudentWhatsappNotifier $whatsapp
    ) {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'category' => ['nullable', 'in:umum,diniyah'],
        ]);

        $category = $data['category'] ?? null;
        $activeActivity = $activityService->getActiveActivity($category);

        if (!$activeActivity) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada kegiatan aktif saat ini.',
            ], 422);
        }

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

        $academicYear = Student::academicYearForDate();

        if (! $student->isObligatedForActivity($activeActivity->category, $academicYear)) {
            $message = $student->isAwayFromBoarding()
                ? 'Siswa sedang berstatus pulang. Scan kembali ke Pondok terlebih dahulu.'
                : ($activeActivity->category === 'diniyah'
                    ? 'Siswa belum terdaftar aktif di MADAD tahun ajaran '.$academicYear.'.'
                    : 'Santri tidak mukim tidak memiliki kewajiban kegiatan Pondok.');

            return response()->json([
                'ok' => false,
                'message' => $message,
            ], 422);
        }

        $session = $sessionService->getOrCreateCurrentSession($activeActivity);

        if ($session->status === 'closed') {
            return response()->json([
                'ok' => false,
                'message' => 'Sesi '.$activeActivity->name.' sudah ditutup.',
            ], 422);
        }

        $existing = ActivityAttendance::where('activity_session_id', $session->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing) {
            return response()->json([
                'ok' => true,
                'already' => true,
                'message' => 'Sudah absen untuk '.$activeActivity->name.'.',
                'activity' => $activeActivity->name,
                'status' => $existing->status,
                'scanned_at' => $existing->scanned_at->format('H:i:s'),
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

        $status = $activityService->isLate($activeActivity) ? 'terlambat' : 'hadir';

        $attendance = ActivityAttendance::create([
            'activity_session_id' => $session->id,
            'student_id' => $student->id,
            'scanned_at' => now(),
            'status' => $status,
        ]);

        $institution = Institution::query()
            ->where('code', $activeActivity->category === 'diniyah' ? 'madad' : 'ponpes')
            ->first();
        if ($institution) {
            $whatsapp->attendance(
                $institution,
                $student,
                $activeActivity->category === 'diniyah' ? 'Kegiatan MADAD' : 'Kegiatan Pondok',
                $activeActivity->name,
                $status,
                $attendance->scanned_at
            );
        }

        return response()->json([
            'ok' => true,
            'already' => false,
            'message' => 'Berhasil absen '.$activeActivity->name.' ('.$status.').',
            'activity' => $activeActivity->name,
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

    private function categoryFromRequest(Request $request): ?string
    {
        $category = $request->query('category');

        return in_array($category, ['umum', 'diniyah'], true) ? $category : null;
    }
}
