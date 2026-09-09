<?php

namespace App\Http\Controllers;

use App\Exports\SchoolTeachersExport;
use App\Imports\SchoolTeachersImport;
use App\Models\Institution;
use App\Models\SchoolTeacher;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class SchoolTeacherController extends Controller
{
    public function index(Request $request, Institution $institution)
    {
        $this->authorizeInstitution($institution);

        $q = $this->normalizeFilter($request->query('q'), 100);
        $levelOptions = $this->levelOptions($institution);
        $requestedLevel = $request->query('level');
        $level = is_string($requestedLevel) && array_key_exists($requestedLevel, $levelOptions)
            ? $requestedLevel
            : null;
        $status = in_array($request->query('status'), ['active', 'inactive'], true)
            ? $request->query('status')
            : null;

        $baseQuery = SchoolTeacher::query()
            ->where('institution_id', $institution->id);

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('is_active', true)->count(),
            'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
        ];

        $teachers = (clone $baseQuery)
            ->when($q, function ($query) use ($q) {
                $query->where(function ($item) use ($q) {
                    $item->where('name', 'like', "%{$q}%")
                        ->orWhere('teacher_code', 'like', "%{$q}%");
                });
            })
            ->when($level, fn ($query) => $query->where('level', $level))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('level')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('school-teachers.index', compact(
            'institution',
            'q',
            'level',
            'levelOptions',
            'status',
            'summary',
            'teachers'
        ));
    }

    public function store(Request $request, Institution $institution)
    {
        $this->authorizeInstitution($institution);

        $data = $request->validate($this->rules($institution));
        $data['institution_id'] = $institution->id;
        $data['teacher_code'] = Str::upper(trim($data['teacher_code']));
        $data['name'] = trim($data['name']);
        $data['phone'] = filled($data['phone'] ?? null) ? trim($data['phone']) : null;
        $data['is_active'] = $request->boolean('is_active');

        SchoolTeacher::create($data);

        return redirect()
            ->route('school-teachers.index', $institution)
            ->with('success', 'Data guru berhasil ditambahkan. QR dibuat otomatis dan akan tetap sama.');
    }

    public function edit(Institution $institution, SchoolTeacher $teacher)
    {
        $this->authorizeInstitution($institution);
        $this->authorizeTeacher($institution, $teacher);

        $levelOptions = $this->levelOptions($institution);

        return view('school-teachers.edit', compact('institution', 'teacher', 'levelOptions'));
    }

    public function update(Request $request, Institution $institution, SchoolTeacher $teacher)
    {
        $this->authorizeInstitution($institution);
        $this->authorizeTeacher($institution, $teacher);

        $data = $request->validate($this->rules($institution, $teacher));
        $data['teacher_code'] = Str::upper(trim($data['teacher_code']));
        $data['name'] = trim($data['name']);
        $data['phone'] = filled($data['phone'] ?? null) ? trim($data['phone']) : null;
        $data['is_active'] = $request->boolean('is_active');

        $teacher->update($data);

        return redirect()
            ->route('school-teachers.index', $institution)
            ->with('success', 'Data guru berhasil diperbarui. QR lama tetap digunakan.');
    }

    public function destroy(Institution $institution, SchoolTeacher $teacher)
    {
        $this->authorizeInstitution($institution);
        $this->authorizeTeacher($institution, $teacher);
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        if ($teacher->attendances()->exists() || $teacher->attendanceExcuses()->exists()) {
            return back()->with(
                'error',
                'Guru tidak dapat dihapus karena sudah memiliki riwayat absensi/izin. Ubah status menjadi Nonaktif.'
            );
        }

        $teacher->delete();

        return redirect()
            ->route('school-teachers.index', $institution)
            ->with('success', 'Data guru yang belum terpakai berhasil dihapus.');
    }

    public function import(Request $request, Institution $institution)
    {
        $this->authorizeInstitution($institution);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $import = new SchoolTeachersImport($institution);
        Excel::import($import, $request->file('file'));

        return redirect()
            ->route('school-teachers.index', $institution)
            ->with(
                'success',
                "Import guru selesai. Baru: {$import->created}, diperbarui: {$import->updated}, dilewati: {$import->skipped}. QR guru lama tidak berubah."
            );
    }

    public function template(Institution $institution)
    {
        $this->authorizeInstitution($institution);

        $examples = match ($institution->code) {
            'mi' => [
                ['MI-G001', 'Ahmad Fauzi', 'laki-laki', 'MI', '081234567890', 'aktif'],
                ['MI-G002', 'Siti Aminah', 'perempuan', 'MI', '081234567891', 'aktif'],
            ],
            'mts' => [
                ['G001', 'Ahmad Fauzi', 'laki-laki', 'MTs', '081234567890', 'aktif'],
                ['G002', 'Siti Aminah', 'perempuan', 'MTs', '081234567891', 'aktif'],
            ],
            'ma' => [
                ['G001', 'Ahmad Fauzi', 'laki-laki', 'MA', '081234567890', 'aktif'],
                ['G002', 'Siti Aminah', 'perempuan', 'MA', '081234567891', 'aktif'],
            ],
            default => [],
        };

        $export = new class($examples) implements FromArray, ShouldAutoSize, WithHeadings {
            public function __construct(
                private readonly array $examples
            ) {}

            public function headings(): array
            {
                return [
                    'kode_guru',
                    'nama',
                    'jenis_kelamin',
                    'jenjang',
                    'no_hp',
                    'status',
                ];
            }

            public function array(): array
            {
                return $this->examples;
            }
        };

        return Excel::download($export, 'template-import-guru-'.$institution->code.'.xlsx');
    }

    public function export(Request $request, Institution $institution)
    {
        $this->authorizeInstitution($institution);

        $q = $this->normalizeFilter($request->query('q'), 100);
        $levelOptions = $this->levelOptions($institution);
        $requestedLevel = $request->query('level');
        $level = is_string($requestedLevel) && array_key_exists($requestedLevel, $levelOptions)
            ? $requestedLevel
            : null;
        $status = in_array($request->query('status'), ['active', 'inactive'], true)
            ? $request->query('status')
            : null;

        return Excel::download(
            new SchoolTeachersExport($institution->id, $q, $level, $status),
            'data-guru-'.$institution->code.'-'.today()->format('Y-m-d').'.xlsx'
        );
    }

    public function qr(Institution $institution, SchoolTeacher $teacher)
    {
        $this->authorizeInstitution($institution);
        $this->authorizeTeacher($institution, $teacher);

        $result = $this->buildQr($teacher, 300, 10);

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType());
    }

    public function downloadQr(Institution $institution, SchoolTeacher $teacher)
    {
        $this->authorizeInstitution($institution);
        $this->authorizeTeacher($institution, $teacher);

        $result = $this->buildQr($teacher, 800, 20);
        $filename = 'QR-Guru-'.Str::slug($teacher->teacher_code).'.png';

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    private function buildQr(SchoolTeacher $teacher, int $size, int $margin)
    {
        return (new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            validateResult: false,
            data: $teacher->qr_token,
            size: $size,
            margin: $margin
        ))->build();
    }

    private function rules(Institution $institution, ?SchoolTeacher $teacher = null): array
    {
        return [
            'teacher_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('school_teachers', 'teacher_code')
                    ->where(fn ($query) => $query->where('institution_id', $institution->id))
                    ->ignore($teacher?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'gender' => ['nullable', 'in:putra,putri'],
            'level' => ['required', Rule::in(array_keys($this->levelOptions($institution)))],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function authorizeInstitution(Institution $institution): void
    {
        abort_unless(
            $institution->is_active
                && in_array($institution->code, ['mi', 'mts', 'ma'], true),
            404
        );

        $user = auth()->user();
        $hasAccess = $user->hasRole('admin') || $user->institutions()
            ->where('institutions.id', $institution->id)
            ->wherePivot('is_active', true)
            ->exists();

        abort_unless($hasAccess, 403, 'Anda tidak memiliki akses ke lembaga ini.');
    }

    private function authorizeTeacher(Institution $institution, SchoolTeacher $teacher): void
    {
        abort_unless($teacher->institution_id === $institution->id, 404);
    }

    private function levelOptions(Institution $institution): array
    {
        return match ($institution->code) {
            'mi' => ['mi' => 'MI'],
            'mts' => ['mts' => 'MTs'],
            'ma' => ['ma' => 'MA'],
            default => [],
        };
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
