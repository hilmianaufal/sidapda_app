<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolAttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SchoolAttendanceSettingController extends Controller
{
    public function edit(Institution $institution)
    {
        $this->ensureSchoolInstitution($institution);

        $setting = SchoolAttendanceSetting::query()
            ->where('institution_id', $institution->id)
            ->first() ?? new SchoolAttendanceSetting($this->defaults());

        return view('school-attendance-settings.edit', compact('institution', 'setting'));
    }

    public function update(Request $request, Institution $institution)
    {
        $this->ensureSchoolInstitution($institution);

        $data = $request->validate([
            'check_in_open' => ['required', 'date_format:H:i'],
            'check_in_time' => ['required', 'date_format:H:i'],
            'check_in_deadline' => ['required', 'date_format:H:i'],
            'check_out_open' => ['required', 'date_format:H:i'],
            'check_out_time' => ['required', 'date_format:H:i'],
            'check_out_deadline' => ['required', 'date_format:H:i'],
        ]);

        if (! $this->isOrdered(
            $data['check_in_open'],
            $data['check_in_time'],
            $data['check_in_deadline']
        )) {
            throw ValidationException::withMessages([
                'check_in_time' => 'Urutan jam masuk harus: buka scan, jam resmi, lalu batas akhir.',
            ]);
        }

        if (! $this->isOrdered(
            $data['check_out_open'],
            $data['check_out_time'],
            $data['check_out_deadline']
        )) {
            throw ValidationException::withMessages([
                'check_out_time' => 'Urutan jam pulang harus: buka scan, jam resmi, lalu batas akhir.',
            ]);
        }

        foreach ($data as $field => $time) {
            $data[$field] = $time.':00';
        }

        $data['is_active'] = true;

        SchoolAttendanceSetting::query()->updateOrCreate(
            ['institution_id' => $institution->id],
            $data
        );

        return redirect()
            ->route('school-attendance-settings.edit', $institution)
            ->with('success', 'Pengaturan waktu '.$institution->short_name.' berhasil disimpan.');
    }

    private function ensureSchoolInstitution(Institution $institution): void
    {
        abort_unless(in_array($institution->code, ['mi', 'sekolah-pagi'], true), 404);
    }

    private function isOrdered(string $open, string $official, string $deadline): bool
    {
        return $this->minutes($open) <= $this->minutes($official)
            && $this->minutes($official) <= $this->minutes($deadline);
    }

    private function minutes(string $time): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return ($hour * 60) + $minute;
    }

    private function defaults(): array
    {
        return [
            'check_in_open' => '05:00:00',
            'check_in_time' => '07:20:00',
            'check_in_deadline' => '09:00:00',
            'check_out_open' => '09:01:00',
            'check_out_time' => '13:10:00',
            'check_out_deadline' => '14:00:00',
            'is_active' => true,
        ];
    }
}
