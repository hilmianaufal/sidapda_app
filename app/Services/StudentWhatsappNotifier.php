<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\InstitutionWhatsappLog;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentWhatsappNotifier
{
    public function __construct(private readonly FonnteService $fonnte)
    {
    }

    public function attendance(
        Institution $institution,
        Student $student,
        string $module,
        string $subject,
        string $status,
        Carbon $at
    ): void {
        if (! $institution->fonnte_notify_attendance) {
            return;
        }

        $statusLabel = match ($status) {
            'terlambat' => 'TERLAMBAT',
            'pulang_cepat' => 'PULANG CEPAT',
            'tepat_waktu' => 'TEPAT WAKTU',
            default => strtoupper(str_replace('_', ' ', $status)),
        };

        $message = $this->header($institution, $student)
            ."Absensi: {$module}\n"
            .($subject !== '' ? "Kegiatan: {$subject}\n" : '')
            ."Status: *{$statusLabel}*\n"
            .'Waktu: '.$at->format('d-m-Y H:i').' WIB'
            .$this->footer($institution);

        $this->defer($institution, $student, 'attendance', $message);
    }

    public function excuse(
        Institution $institution,
        Student $student,
        string $subject,
        string $status,
        string $date,
        ?string $notes = null
    ): void {
        if (! $institution->fonnte_notify_excuse) {
            return;
        }

        $message = $this->header($institution, $student)
            ."Status: *".strtoupper($status)."*\n"
            ."Kegiatan: {$subject}\n"
            .'Tanggal: '.Carbon::parse($date)->format('d-m-Y')
            .($notes ? "\nCatatan: {$notes}" : '')
            .$this->footer($institution);

        $this->defer($institution, $student, 'excuse', $message);
    }

    public function boarding(
        Institution $institution,
        Student $student,
        string $movement,
        Carbon $at,
        ?string $reason = null
    ): void {
        if (! $institution->fonnte_notify_boarding) {
            return;
        }

        $movementLabel = $movement === 'depart' ? 'PULANG DARI PONDOK' : 'KEMBALI KE PONDOK';
        $message = $this->header($institution, $student)
            ."Pergerakan: *{$movementLabel}*\n"
            .'Waktu: '.$at->format('d-m-Y H:i').' WIB'
            .($reason ? "\nKeperluan: {$reason}" : '')
            .$this->footer($institution);

        $this->defer($institution, $student, 'boarding', $message);
    }

    private function defer(
        Institution $institution,
        Student $student,
        string $event,
        string $message
    ): void {
        $target = $this->normalizePhone($student->parent_phone);

        try {
            $gatewayReady = $institution->fonnte_enabled && filled($institution->fonnte_token);
        } catch (Throwable $exception) {
            Log::warning('Konfigurasi Fonnte tidak dapat dibaca.', [
                'institution_id' => $institution->id,
                'exception' => $exception::class,
            ]);
            return;
        }

        if (! $gatewayReady || $target === null) {
            return;
        }

        $institutionId = $institution->id;
        $studentId = $student->id;

        app()->terminating(function () use ($institutionId, $studentId, $event, $target, $message) {
            $log = null;

            try {
                $institution = Institution::find($institutionId);

                if (! $institution || ! $institution->fonnte_enabled || blank($institution->fonnte_token)) {
                    return;
                }

                $log = InstitutionWhatsappLog::create([
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'event' => $event,
                    'target' => $target,
                    'message' => $message,
                    'status' => 'processing',
                ]);

                $result = $this->fonnte->send($institution, $target, $message);
                $requestId = data_get($result, 'data.requestid');

                $log->update([
                    'status' => $result['ok'] ? 'accepted' : 'failed',
                    'request_id' => $requestId !== null ? (string) $requestId : null,
                    'error' => $result['ok'] ? null : ($result['error'] ?: 'Fonnte menolak permintaan.'),
                    'response' => $result['data'],
                    'sent_at' => $result['ok'] ? now() : null,
                ]);
            } catch (Throwable $exception) {
                if ($log) {
                    try {
                        $log->update([
                            'status' => 'failed',
                            'error' => mb_substr($exception->getMessage(), 0, 1000),
                        ]);
                    } catch (Throwable) {
                        // Absensi tetap berhasil walaupun pencatatan log juga bermasalah.
                    }
                }

                Log::warning('Pengiriman Fonnte gagal.', [
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'event' => $event,
                    'exception' => $exception::class,
                ]);
            }
        });
    }

    private function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return preg_match('/^\d{9,16}$/', $digits) ? $digits : null;
    }

    private function header(Institution $institution, Student $student): string
    {
        return "*{$institution->short_name}*\n"
            ."Yth. Orang Tua/Wali {$student->name},\n\n";
    }

    private function footer(Institution $institution): string
    {
        $footer = trim((string) $institution->fonnte_message_footer);

        return "\n\n".($footer !== '' ? $footer : 'Pesan otomatis SIDAPDA, mohon tidak membalas.');
    }
}
