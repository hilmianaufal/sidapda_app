<?php

namespace App\Http\Controllers;

use App\Models\BoardingLeave;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\Institution;
use App\Services\StudentWhatsappNotifier;

class BoardingMovementController extends Controller
{
    public function index(): View
    {
        $activeLeaves = BoardingLeave::query()
            ->with('student')
            ->active()
            ->latest('departed_at')
            ->paginate(15, ['*'], 'pulang');

        $recentReturns = BoardingLeave::query()
            ->with('student')
            ->whereNotNull('returned_at')
            ->latest('returned_at')
            ->limit(10)
            ->get();

        $awayCount = BoardingLeave::query()->active()->count();
        $returnedToday = BoardingLeave::query()
            ->whereDate('returned_at', now()->toDateString())
            ->count();

        return view('boarding-movements.index', compact(
            'activeLeaves',
            'recentReturns',
            'awayCount',
            'returnedToday'
        ));
    }

    public function store(Request $request, StudentWhatsappNotifier $whatsapp): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'movement' => ['required', 'in:depart,return'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $student = Student::query()
                ->where('qr_token', $data['token'])
                ->lockForUpdate()
                ->first();

            if (! $student) {
                return [
                    'http' => 404,
                    'payload' => [
                        'ok' => false,
                        'message' => 'QR tidak dikenal atau siswa tidak ditemukan.',
                    ],
                ];
            }

            if (! $student->is_active) {
                return [
                    'http' => 422,
                    'payload' => [
                        'ok' => false,
                        'message' => 'Siswa nonaktif. Hubungi admin.',
                    ],
                ];
            }

            if ($student->residency_status !== 'mukim') {
                return [
                    'http' => 422,
                    'payload' => [
                        'ok' => false,
                        'message' => 'Fitur pulang pondok hanya untuk santri mukim.',
                    ],
                ];
            }

            $activeLeave = BoardingLeave::query()
                ->where('student_id', $student->id)
                ->active()
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($data['movement'] === 'depart') {
                if ($activeLeave) {
                    return [
                        'http' => 200,
                        'payload' => $this->payload(
                            $student,
                            $activeLeave,
                            'depart',
                            'Siswa sudah berstatus pulang sejak '.$activeLeave->departed_at->format('d-m-Y H:i').'.',
                            true
                        ),
                    ];
                }

                $leave = BoardingLeave::create([
                    'student_id' => $student->id,
                    'departed_at' => now(),
                    'reason' => $data['reason'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'departed_by' => auth()->id(),
                ]);

                return [
                    'http' => 201,
                    'payload' => $this->payload(
                        $student,
                        $leave,
                        'depart',
                        'Berhasil mencatat siswa pulang. Kewajiban Pondok dinonaktifkan sementara.'
                    ),
                ];
            }

            if (! $activeLeave) {
                return [
                    'http' => 200,
                    'payload' => [
                        'ok' => true,
                        'already' => true,
                        'movement' => 'return',
                        'message' => 'Siswa sudah berada di Pondok; tidak ada status pulang aktif.',
                        'student' => $this->studentData($student),
                    ],
                ];
            }

            $activeLeave->update([
                'returned_at' => now(),
                'returned_by' => auth()->id(),
            ]);
            $activeLeave->refresh();

            return [
                'http' => 200,
                'payload' => $this->payload(
                    $student,
                    $activeLeave,
                    'return',
                    'Berhasil mencatat siswa kembali. Kewajiban Pondok sudah aktif lagi.'
                ),
            ];
        });

        if (($result['payload']['ok'] ?? false)
            && ! ($result['payload']['already'] ?? false)
            && isset($result['payload']['student']['id'])) {
            $institution = Institution::query()->where('code', 'ponpes')->first();
            $student = Student::find($result['payload']['student']['id']);
            $movement = $result['payload']['movement'] ?? $data['movement'];
            $eventAt = $movement === 'depart'
                ? data_get($result, 'payload.departed_at')
                : data_get($result, 'payload.returned_at');

            if ($institution && $student && $eventAt) {
                $whatsapp->boarding(
                    $institution,
                    $student,
                    $movement,
                    \Illuminate\Support\Carbon::createFromFormat('d-m-Y H:i:s', $eventAt),
                    $movement === 'depart' ? ($data['reason'] ?? null) : null
                );
            }
        }

        return response()->json($result['payload'], $result['http']);
    }

    private function payload(
        Student $student,
        BoardingLeave $leave,
        string $movement,
        string $message,
        bool $already = false
    ): array {
        return [
            'ok' => true,
            'already' => $already,
            'movement' => $movement,
            'message' => $message,
            'departed_at' => $leave->departed_at?->format('d-m-Y H:i:s'),
            'returned_at' => $leave->returned_at?->format('d-m-Y H:i:s'),
            'student' => $this->studentData($student),
        ];
    }

    private function studentData(Student $student): array
    {
        return [
            'id' => $student->id,
            'nis' => $student->nis,
            'name' => $student->name,
            'kelas' => $student->kelas,
            'kamar' => $student->kamar,
            'photo_url' => $student->photoUrl(),
        ];
    }
}
