<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\InstitutionWhatsappLog;
use App\Services\FonnteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class InstitutionSettingController extends Controller
{
    public function edit(Institution $institution): View
    {
        $this->authorizeSettings($institution);

        $logs = $institution->whatsappLogs()
            ->with('student:id,name')
            ->latest()
            ->limit(10)
            ->get();

        return view('institution-settings.edit', compact('institution', 'logs'));
    }

    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $this->authorizeSettings($institution);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:1500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'leader_name' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
            'fonnte_enabled' => ['required', 'boolean'],
            'fonnte_token' => ['nullable', 'string', 'max:1000'],
            'fonnte_notify_attendance' => ['required', 'boolean'],
            'fonnte_notify_excuse' => ['required', 'boolean'],
            'fonnte_notify_boarding' => ['required', 'boolean'],
            'fonnte_message_footer' => ['nullable', 'string', 'max:255'],
        ], [
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
            'logo.mimes' => 'Logo harus berupa JPG, JPEG, PNG, atau WEBP.',
            'website.url' => 'Alamat website harus berupa URL lengkap, misalnya https://contoh.sch.id.',
        ]);

        if ((bool) $data['fonnte_enabled']
            && blank($data['fonnte_token'] ?? null)
            && blank($institution->fonnte_token)) {
            return back()
                ->withInput()
                ->withErrors(['fonnte_token' => 'Isi token Fonnte sebelum mengaktifkan WhatsApp Gateway.']);
        }

        if ($request->boolean('remove_logo') && $institution->logo_path) {
            Storage::disk('public_uploads')->delete($institution->logo_path);
            $institution->logo_path = null;
        }

        if ($request->hasFile('logo')) {
            if ($institution->logo_path) {
                Storage::disk('public_uploads')->delete($institution->logo_path);
            }

            $logo = $request->file('logo');
            $filename = Str::uuid().'.'.strtolower($logo->getClientOriginalExtension());
            $institution->logo_path = $logo->storeAs(
                'institutions/'.$institution->code,
                $filename,
                'public_uploads'
            );
        }

        $institution->fill([
            'name' => trim($data['name']),
            'short_name' => trim($data['short_name']),
            'description' => $this->nullableText($data['description'] ?? null),
            'address' => $this->nullableText($data['address'] ?? null),
            'phone' => $this->nullableText($data['phone'] ?? null),
            'email' => $this->nullableText($data['email'] ?? null),
            'website' => $this->nullableText($data['website'] ?? null),
            'leader_name' => $this->nullableText($data['leader_name'] ?? null),
            'fonnte_enabled' => (bool) $data['fonnte_enabled'],
            'fonnte_notify_attendance' => (bool) $data['fonnte_notify_attendance'],
            'fonnte_notify_excuse' => (bool) $data['fonnte_notify_excuse'],
            'fonnte_notify_boarding' => (bool) $data['fonnte_notify_boarding'],
            'fonnte_message_footer' => $this->nullableText($data['fonnte_message_footer'] ?? null),
        ]);

        if (filled($data['fonnte_token'] ?? null)) {
            $institution->fonnte_token = trim($data['fonnte_token']);
            $institution->fonnte_device_number = null;
            $institution->fonnte_device_status = null;
            $institution->fonnte_last_tested_at = null;
        }

        $institution->save();

        return redirect()
            ->route('institution-settings.edit', $institution)
            ->with('success', 'Profil dan pengaturan '.$institution->short_name.' berhasil disimpan.');
    }

    public function test(Institution $institution, FonnteService $fonnte): RedirectResponse
    {
        $this->authorizeSettings($institution);

        if (blank($institution->fonnte_token)) {
            return back()->with('error', 'Token Fonnte belum disimpan untuk lembaga ini.');
        }

        try {
            $result = $fonnte->deviceProfile($institution);
            $data = $result['data'];

            $institution->update([
                'fonnte_device_number' => $data['device'] ?? null,
                'fonnte_device_status' => $result['ok']
                    ? ($data['device_status'] ?? 'connect')
                    : 'error',
                'fonnte_last_tested_at' => now(),
            ]);

            if (! $result['ok']) {
                return back()->with('error', 'Koneksi Fonnte gagal: '.($result['error'] ?: 'respons tidak valid.'));
            }

            return back()->with(
                'success',
                'Token Fonnte valid. Perangkat '.($data['device'] ?? '-').' berstatus '.strtoupper($data['device_status'] ?? 'connect').'.'
            );
        } catch (Throwable $exception) {
            $institution->update([
                'fonnte_device_status' => 'error',
                'fonnte_last_tested_at' => now(),
            ]);

            return back()->with('error', 'Tidak dapat menghubungi Fonnte: '.$exception->getMessage());
        }
    }

    public function sendTest(
        Request $request,
        Institution $institution,
        FonnteService $fonnte
    ): RedirectResponse {
        $this->authorizeSettings($institution);

        $data = $request->validate([
            'target' => ['required', 'string', 'max:30'],
        ]);

        if (blank($institution->fonnte_token)) {
            return back()->with('error', 'Token Fonnte belum disimpan untuk lembaga ini.');
        }

        $target = $this->normalizePhone($data['target']);
        if ($target === null) {
            return back()->withErrors(['target' => 'Nomor WhatsApp tidak valid. Gunakan contoh 081234567890.']);
        }

        $message = "*{$institution->short_name}*\nPesan tes WhatsApp Gateway SIDAPDA berhasil dikirim."
            ."\n\n".($institution->fonnte_message_footer ?: 'Pesan otomatis SIDAPDA, mohon tidak membalas.');

        $log = InstitutionWhatsappLog::create([
            'institution_id' => $institution->id,
            'event' => 'test',
            'target' => $target,
            'message' => $message,
            'status' => 'processing',
        ]);

        try {
            $result = $fonnte->send($institution, $target, $message);
            $requestId = data_get($result, 'data.requestid');

            $log->update([
                'status' => $result['ok'] ? 'accepted' : 'failed',
                'request_id' => $requestId !== null ? (string) $requestId : null,
                'error' => $result['ok'] ? null : ($result['error'] ?: 'Fonnte menolak permintaan.'),
                'response' => $result['data'],
                'sent_at' => $result['ok'] ? now() : null,
            ]);

            return $result['ok']
                ? back()->with('success', 'Pesan tes sudah diterima antrean Fonnte.')
                : back()->with('error', 'Pesan tes ditolak Fonnte: '.($result['error'] ?: 'respons tidak valid.'));
        } catch (Throwable $exception) {
            $log->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);

            return back()->with('error', 'Pesan tes gagal diproses: '.$exception->getMessage());
        }
    }

    private function authorizeSettings(Institution $institution): void
    {
        abort_unless($institution->is_active, 404);
        abort_unless(
            auth()->user()?->canManageInstitutionSettings($institution),
            403,
            'Anda tidak memiliki izin mengubah pengaturan lembaga ini.'
        );
    }

    private function nullableText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

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
}
