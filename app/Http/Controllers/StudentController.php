<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Imports\StudentsImport;
use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $kelas = $request->query('kelas');
        $kamar = $request->query('kamar');
        $gender = $request->query('gender');
        $institutions = $this->accessibleInstitutions();
        $institutionId = $request->integer('institution_id') ?: null;

        if ($institutionId && ! $institutions->contains('id', $institutionId)) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        if (! $institutionId && ! auth()->user()->hasRole('admin') && $institutions->count() === 1) {
            $institutionId = (int) $institutions->first()->id;
        }

        $selectedInstitution = $institutions->firstWhere('id', $institutionId);
        $institutionClass = $request->query('institution_class');
        $academicYear = $this->currentAcademicYear();
        $canViewBoardingData = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');

        if ($selectedInstitution?->code === 'ponpes') {
            $institutionClass = null;
        }

        if (! $canViewBoardingData) {
            $kelas = null;
            $kamar = null;
        }

        $baseStudents = $this->studentQueryForAccess(
            Student::query(),
            $selectedInstitution,
            $academicYear
        );

        $students = (clone $baseStudents)
            ->with(['enrollments' => function ($query) use ($academicYear, $selectedInstitution, $institutions) {
                $query->where('academic_year', $academicYear)
                    ->where('is_active', true)
                    ->when(
                        $selectedInstitution && $selectedInstitution->code !== 'ponpes',
                        fn ($item) => $item->where('institution_id', $selectedInstitution->id)
                    )
                    ->when(
                        ! $selectedInstitution && ! auth()->user()->hasRole('admin'),
                        fn ($item) => $item->whereIn('institution_id', $institutions->pluck('id'))
                    );
            }])
            ->when($q, fn($qr) => $qr->where(function($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                ->orWhere('nis', 'like', "%$q%");
            }))
            ->when($kelas, fn($qr) => $qr->where('kelas', $kelas))
            ->when($kamar, fn($qr) => $qr->where('kamar', $kamar))
            ->when($gender, fn($qr) => $qr->where('gender', $gender))
            ->when($institutionClass, function ($query) use ($selectedInstitution, $institutionClass, $academicYear) {
                $query->whereHas('enrollments', function ($enrollment) use ($selectedInstitution, $institutionClass, $academicYear) {
                    $enrollment->where('academic_year', $academicYear)
                        ->where('is_active', true)
                        ->when($selectedInstitution, fn ($item) => $item->where('institution_id', $selectedInstitution->id))
                        ->when($institutionClass, fn ($item) => $item->where('class_name', $institutionClass));
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $kelasList = $canViewBoardingData
            ? (clone $baseStudents)->whereNotNull('kelas')->distinct()->orderBy('kelas')->pluck('kelas')
            : collect();
        $kamarList = $canViewBoardingData
            ? (clone $baseStudents)->whereNotNull('kamar')->distinct()->orderBy('kamar')->pluck('kamar')
            : collect();
        $institutionClasses = $selectedInstitution && $selectedInstitution->code !== 'ponpes'
            ? StudentEnrollment::where('institution_id', $institutionId)
                ->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->whereNotNull('class_name')
                ->where('class_name', '!=', '')
                ->distinct()
                ->orderBy('class_name')
                ->pluck('class_name')
            : collect();

        return view('students.index', compact(
            'students',
            'q',
            'kelas',
            'kamar',
            'gender',
            'institutionId',
            'institutionClass',
            'institutions',
            'institutionClasses',
            'academicYear',
            'kelasList',
            'kamarList',
            'canViewBoardingData'
        ));
    }

    public function create(Request $request)
    {
        $institutions = $this->accessibleInstitutions();
        $selectedInstitutionId = $request->integer('institution_id') ?: null;

        if ($selectedInstitutionId && ! $institutions->contains('id', $selectedInstitutionId)) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        if (! $selectedInstitutionId && ! auth()->user()->hasRole('admin') && $institutions->count() === 1) {
            $selectedInstitutionId = (int) $institutions->first()->id;
        }

        return view('students.create', compact('institutions', 'selectedInstitutionId'));
    }

    public function store(Request $request)
    {
        $canManageBoarding = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');

        $data = $request->validate([
            'nis'       => ['required','string','max:50','unique:students,nis'],
            'name'      => ['required','string','max:120'],
            'kelas'     => ['nullable','string','max:50'],
            'kamar'     => ['nullable','string','max:50'],
             'parent_phone' => ['nullable', 'string', 'max:30'],
             'gender' => ['nullable', 'in:putra,putri'],
            'residency_status' => ['required', 'in:mukim,non_mukim'],
            'is_active' => ['nullable','boolean'],
            'photo'     => ['nullable','image','mimes:jpg,jpeg,png','max:2048'],
            'institution_id' => [
                Rule::requiredIf(fn () => ! auth()->user()->hasRole('admin')),
                'nullable',
                'integer',
                Rule::exists('institutions', 'id')->where('is_active', true),
            ],
            'institution_class' => ['nullable', 'string', 'max:50'],
            'institution_level' => ['nullable', 'string', 'max:20'],
        ]);

        $institution = ! empty($data['institution_id'])
            ? Institution::findOrFail($data['institution_id'])
            : null;

        if ($institution && ! auth()->user()->canAccessInstitution($institution)) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        if ($institution?->code === 'madad') {
            $level = StudentEnrollment::normalizeMadadLevel($data['institution_level'] ?? null);

            if ($level === null) {
                throw ValidationException::withMessages([
                    'institution_level' => 'Pilih jenjang MADAD: Ula, Wustha, atau Ulya.',
                ]);
            }

            $data['institution_level'] = $level;
        }

        if ($institution?->code === 'ponpes') {
            $data['residency_status'] = 'mukim';
        }

        if (! $canManageBoarding) {
            $data['kelas'] = null;
            $data['kamar'] = null;
            $data['residency_status'] = 'non_mukim';
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        if ($request->hasFile('photo')) {
            $dir = public_path('uploads/students');

            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $file->move($dir, $filename);

            $data['photo'] = 'uploads/students/' . $filename;
        }

        $enrollmentData = [
            'institution_class' => $data['institution_class'] ?? null,
            'institution_level' => $data['institution_level'] ?? null,
        ];
        unset($data['institution_id'], $data['institution_class'], $data['institution_level']);

        $student = DB::transaction(function () use ($data, $institution, $enrollmentData) {
            $student = Student::create($data);

            if ($institution) {
                $student->enrollments()->create([
                    'institution_id' => $institution->id,
                    'academic_year' => $this->currentAcademicYear(),
                    'class_name' => filled($enrollmentData['institution_class'])
                        ? trim($enrollmentData['institution_class'])
                        : ($student->kelas ?: null),
                    'level' => filled($enrollmentData['institution_level'])
                        ? trim($enrollmentData['institution_level'])
                        : null,
                    'is_active' => true,
                    'enrolled_at' => now()->toDateString(),
                    'left_at' => null,
                ]);
            }

            return $student;
        });

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Santri berhasil ditambahkan.');
    }

    public function show(Student $student)
    {
        $this->authorizeStudent($student);

        $academicYear = now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;

        $institutionIds = $this->accessibleInstitutions()->pluck('id');
        $canViewBoardingData = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');
        $student->load(['enrollments' => function ($query) use ($academicYear, $institutionIds) {
            $query->where('academic_year', $academicYear)
                ->where('is_active', true)
                ->when(! auth()->user()->hasRole('admin'), fn ($item) => $item->whereIn('institution_id', $institutionIds))
                ->with('institution')
                ->orderBy('institution_id');
        }]);

        return view('students.show', compact('student', 'academicYear', 'canViewBoardingData'));
    }

    public function edit(Student $student)
    {
        $this->authorizeStudent($student);

        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudent($student);
        $canManageBoarding = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');
        $canManageGlobalStatus = auth()->user()->hasRole('admin');

        $data = $request->validate([
            'nis'       => ['required','string','max:50', Rule::unique('students','nis')->ignore($student->id)],
            'name'      => ['required','string','max:120'],
            'kelas'     => ['nullable','string','max:50'],
            'kamar'     => ['nullable','string','max:50'],
            'gender' => ['nullable', 'in:putra,putri'],
            'residency_status' => [$canManageBoarding ? 'required' : 'nullable', 'in:mukim,non_mukim'],
            'is_active' => ['nullable','boolean'],
             'parent_phone' => ['nullable', 'string', 'max:30'],
            'photo'     => ['nullable','image','mimes:jpg,jpeg,png','max:2048'],
        ]);

        if (! $canManageBoarding) {
            unset($data['kelas'], $data['kamar'], $data['residency_status']);
        }

        if (! $canManageGlobalStatus) {
            unset($data['is_active']);
        }

        if ($canManageGlobalStatus) {
            $data['is_active'] = (bool) ($data['is_active'] ?? false);
        }

        if ($request->hasFile('photo')) {
            if ($student->photo && file_exists(public_path($student->photo))) {
                unlink(public_path($student->photo));
            }

            $dir = public_path('uploads/students');

            if (!File::exists($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $file->move($dir, $filename);

            $data['photo'] = 'uploads/students/' . $filename;
        }

        $student->update($data);

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);

        $historyTables = [
            'attendances',
            'activity_attendances',
            'school_attendances',
            'school_attendance_excuses',
            'school_extracurricular_attendances',
            'school_extracurricular_attendance_excuses',
            'student_promotions',
            'boarding_leaves',
        ];

        $hasHistory = collect($historyTables)->contains(function (string $table) use ($student) {
            return Schema::hasTable($table)
                && DB::table($table)->where('student_id', $student->id)->exists();
        });

        if ($hasHistory) {
            return back()->with(
                'error',
                'Santri tidak dapat dihapus karena sudah memiliki riwayat. Ubah status menjadi Nonaktif agar rekap lama tetap aman.'
            );
        }

        $photo = $student->photo;

        DB::transaction(fn () => $student->delete());

        if ($photo && File::exists(public_path($photo))) {
            File::delete(public_path($photo));
        }

        return redirect()
            ->route('students.index')
            ->with('success', 'Santri berhasil dihapus.');
    }


    public function searchRealtime(Request $request)
    {
        $q = $request->input('q');
        $kelas = $request->input('kelas');
        $kamar = $request->input('kamar');
        $gender = $request->input('gender');
        $institutions = $this->accessibleInstitutions();
        $institutionId = $request->integer('institution_id') ?: null;

        if ($institutionId && ! $institutions->contains('id', $institutionId)) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        if (! $institutionId && ! auth()->user()->hasRole('admin') && $institutions->count() === 1) {
            $institutionId = (int) $institutions->first()->id;
        }

        $selectedInstitution = $institutions->firstWhere('id', $institutionId);
        $institutionClass = $request->input('institution_class');
        $academicYear = $this->currentAcademicYear();
        $canViewBoardingData = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');

        if ($selectedInstitution?->code === 'ponpes') {
            $institutionClass = null;
        }

        if (! $canViewBoardingData) {
            $kelas = null;
            $kamar = null;
        }

        $students = $this->studentQueryForAccess(Student::query(), $selectedInstitution, $academicYear)
            ->with(['enrollments' => function ($query) use ($academicYear, $selectedInstitution, $institutions) {
                $query->where('academic_year', $academicYear)
                    ->where('is_active', true)
                    ->when(
                        $selectedInstitution && $selectedInstitution->code !== 'ponpes',
                        fn ($item) => $item->where('institution_id', $selectedInstitution->id)
                    )
                    ->when(
                        ! $selectedInstitution && ! auth()->user()->hasRole('admin'),
                        fn ($item) => $item->whereIn('institution_id', $institutions->pluck('id'))
                    );
            }])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($s) use ($q) {
                    $s->where('name', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%");
                });
            })
            ->when($kelas, fn ($query) => $query->where('kelas', $kelas))
            ->when($kamar, fn ($query) => $query->where('kamar', $kamar))
            ->when($gender, fn ($query) => $query->where('gender', $gender))
            ->when($institutionClass, function ($query) use ($selectedInstitution, $institutionClass, $academicYear) {
                $query->whereHas('enrollments', function ($enrollment) use ($selectedInstitution, $institutionClass, $academicYear) {
                    $enrollment->where('academic_year', $academicYear)
                        ->where('is_active', true)
                        ->when($selectedInstitution, fn ($item) => $item->where('institution_id', $selectedInstitution->id))
                        ->when($institutionClass, fn ($item) => $item->where('class_name', $institutionClass));
                });
            })
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'kelas' => $canViewBoardingData ? $student->kelas : null,
                'institution_class' => $student->enrollments->first()?->class_name,
                'institution_level' => $student->enrollments->first()?->level,
                'gender' => $student->gender,
                'kamar' => $canViewBoardingData ? $student->kamar : null,
                'is_active' => $student->is_active,
                'photo_url' => $student->photoUrl(),
                'show_url' => route('students.show', $student),
                'edit_url' => route('students.edit', $student),
                'delete_url' => route('students.destroy', $student),
            ]);

        return response()->json([
            'students' => $students,
        ]);
    }

    public function importForm(Request $request)
    {
        $institutions = $this->accessibleInstitutions();
        $selectedInstitutionId = $request->integer('institution_id') ?: null;

        if ($selectedInstitutionId && ! $institutions->contains('id', $selectedInstitutionId)) {
            abort(403, 'Anda tidak memiliki akses ke lembaga ini.');
        }

        if (! $selectedInstitutionId && $institutions->count() === 1) {
            $selectedInstitutionId = (int) $institutions->first()->id;
        }

        return view('students.import', compact('institutions', 'selectedInstitutionId'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'institution_id' => [
                'required',
                Rule::exists('institutions', 'id')->where('is_active', true),
            ],
        ]);

        $institution = Institution::findOrFail($request->integer('institution_id'));
        abort_unless(
            auth()->user()->canAccessInstitution($institution),
            403,
            'Anda tidak memiliki akses ke lembaga ini.'
        );

        $canManageBoarding = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');
        $import = new StudentsImport(
            $institution,
            $this->currentAcademicYear(),
            $canManageBoarding
        );

        Excel::import($import, $request->file('file'));

        return redirect()
            ->route('students.index')
            ->with(
                'success',
                "Import {$institution->short_name} selesai. Siswa baru: {$import->created}, siswa diperbarui: {$import->updated}, keanggotaan baru: {$import->enrolled}, keanggotaan diperbarui: {$import->enrollmentUpdated}, dilewati: {$import->skipped}."
            );
    }
    public function downloadTemplate()
    {
        $export = new class implements FromArray, WithHeadings {
            public function headings(): array
            {
                return [
                    'nis',
                    'nama',
                    'kelas',
                    'kamar',
                    'jenis_santri',
                    'status_mukim',
                    'wa_ortu',
                    'kelas_lembaga',
                    'jenjang_lembaga',
                ];
            }

            public function array(): array
            {
                return [
                        [
                            '2024001',
                            'Ahmad Fauzan',
                            '7A',
                            'Ruqoyah',
                            'putra',
                            'mukim',
                            '6281234567890',
                            '7A',
                            'Ula',
                        ],
                        [
                            '2024002',
                            'Fatimah Zahra',
                            '6A',
                            'Aisyah',
                            'putri',
                            'tidak mukim',
                            '6289876543210',
                            '6A',
                            'MI',
                        ],
                    ];
            }
        };

        return Excel::download($export, 'template-import-santri.xlsx');
    }

    public function exportStudents(Request $request)
    {
        $data = $request->validate([
            'institution_id' => [
                'required',
                Rule::exists('institutions', 'id')->where('is_active', true),
            ],
            'institution_class' => ['nullable', 'string', 'max:50'],
        ]);

        $institution = Institution::findOrFail($data['institution_id']);
        abort_unless(
            auth()->user()->canAccessInstitution($institution),
            403,
            'Anda tidak memiliki akses ke lembaga ini.'
        );

        $className = filled($data['institution_class'] ?? null)
            ? $data['institution_class']
            : null;
        $filename = 'data-siswa-'.Str::slug($institution->short_name)
            .($className ? '-'.Str::slug($className) : '')
            .'-'.str_replace('/', '-', $this->currentAcademicYear()).'.xlsx';

        return Excel::download(
            new StudentsExport($institution->id, $this->currentAcademicYear(), $className),
            $filename
        );
    }

    private function currentAcademicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
    }

    private function accessibleInstitutions()
    {
        return auth()->user()->accessibleInstitutions()->get();
    }

    private function studentQueryForAccess(
        Builder $query,
        ?Institution $institution,
        string $academicYear
    ): Builder {
        if ($institution) {
            if ($institution->code === 'ponpes') {
                return $query->where('residency_status', 'mukim');
            }

            return $query->whereHas('enrollments', fn (Builder $enrollment) => $enrollment
                ->where('institution_id', $institution->id)
                ->where('academic_year', $academicYear)
                ->where('is_active', true));
        }

        return auth()->user()->scopeAccessibleStudents($query, $academicYear);
    }

    private function authorizeStudent(Student $student): void
    {
        abort_unless(
            auth()->user()->canAccessStudent($student, $this->currentAcademicYear()),
            403,
            'Anda tidak memiliki akses ke data siswa ini.'
        );
    }
}
