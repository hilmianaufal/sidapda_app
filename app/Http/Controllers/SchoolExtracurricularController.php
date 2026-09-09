<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\SchoolExtracurricular;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SchoolExtracurricularController extends Controller
{
    public function index(Request $request, Institution $institution): View
    {
        $this->authorizeInstitution($institution);

        $q = $this->normalizeFilter($request->query('q'), 100);
        $level = $institution->code;
        $day = is_numeric($request->query('day'))
            && (int) $request->query('day') >= 0
            && (int) $request->query('day') <= 6
                ? (int) $request->query('day')
                : null;
        $status = in_array($request->query('status'), ['active', 'inactive'], true)
            ? $request->query('status')
            : null;

        $baseQuery = SchoolExtracurricular::query()
            ->where('institution_id', $institution->id);

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $extracurriculars = (clone $baseQuery)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($search) use ($q) {
                    $search->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('coach_name', 'like', "%{$q}%");
                });
            })
            ->when($level, fn ($query) => $query->where('level', $level))
            ->when($day !== null, fn ($query) => $query->where('schedule_day', $day))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('school-extracurriculars.index', compact(
            'institution',
            'q',
            'level',
            'day',
            'status',
            'summary',
            'extracurriculars'
        ));
    }

    public function store(Request $request, Institution $institution): RedirectResponse
    {
        $this->authorizeInstitution($institution);

        $data = $request->validate($this->rules($institution));
        $data['institution_id'] = $institution->id;
        $data['code'] = $this->uniqueCode($institution, $data['name']);
        $data['name'] = trim($data['name']);
        $data['coach_name'] = filled($data['coach_name'] ?? null)
            ? trim($data['coach_name'])
            : null;
        $data['description'] = filled($data['description'] ?? null)
            ? trim($data['description'])
            : null;
        $data['is_active'] = $request->boolean('is_active');

        SchoolExtracurricular::create($data);

        return redirect()
            ->route('school-extracurriculars.index', $institution)
            ->with('success', 'Ekstrakurikuler berhasil ditambahkan.');
    }

    public function edit(
        Institution $institution,
        SchoolExtracurricular $extracurricular
    ): View {
        $this->authorizeInstitution($institution);
        $this->authorizeExtracurricular($institution, $extracurricular);

        return view('school-extracurriculars.edit', compact('institution', 'extracurricular'));
    }

    public function update(
        Request $request,
        Institution $institution,
        SchoolExtracurricular $extracurricular
    ): RedirectResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExtracurricular($institution, $extracurricular);

        $data = $request->validate($this->rules($institution, $extracurricular));
        $data['name'] = trim($data['name']);
        $data['coach_name'] = filled($data['coach_name'] ?? null)
            ? trim($data['coach_name'])
            : null;
        $data['description'] = filled($data['description'] ?? null)
            ? trim($data['description'])
            : null;
        $data['is_active'] = $request->boolean('is_active');

        $extracurricular->update($data);

        return redirect()
            ->route('school-extracurriculars.index', $institution)
            ->with('success', 'Ekstrakurikuler berhasil diperbarui.');
    }

    public function destroy(
        Institution $institution,
        SchoolExtracurricular $extracurricular
    ): RedirectResponse {
        $this->authorizeInstitution($institution);
        $this->authorizeExtracurricular($institution, $extracurricular);
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        if ($extracurricular->attendances()->exists() || $extracurricular->attendanceExcuses()->exists()) {
            return back()->with(
                'error',
                'Ekstrakurikuler tidak dapat dihapus karena sudah memiliki riwayat absensi/izin. Ubah status menjadi Nonaktif.'
            );
        }

        $extracurricular->delete();

        return redirect()
            ->route('school-extracurriculars.index', $institution)
            ->with('success', 'Ekstrakurikuler yang belum terpakai berhasil dihapus.');
    }

    private function rules(
        Institution $institution,
        ?SchoolExtracurricular $extracurricular = null
    ): array {
        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('school_extracurriculars', 'name')
                    ->where(fn ($query) => $query->where('institution_id', $institution->id))
                    ->ignore($extracurricular?->id),
            ],
            'level' => ['required', Rule::in([$institution->code])],
            'schedule_day' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'late_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'coach_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function uniqueCode(Institution $institution, string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? Str::limit($base, 60, '') : 'ekstrakurikuler';
        $code = $base;
        $suffix = 2;

        while (SchoolExtracurricular::query()
            ->where('institution_id', $institution->id)
            ->where('code', $code)
            ->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless($institution->is_active && in_array($institution->code, ['mts', 'ma'], true), 404);

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');
    }

    private function authorizeExtracurricular(
        Institution $institution,
        SchoolExtracurricular $extracurricular
    ): void {
        abort_unless($extracurricular->institution_id === $institution->id, 404);
    }

    private function normalizeFilter(mixed $value, int $maxLength): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' && mb_strlen($value) <= $maxLength
            ? $value
            : null;
    }
}
