<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentInstitutionController extends Controller
{
    public function edit(Student $student)
    {
        $this->authorizeStudent($student);

        $academicYear = $this->currentAcademicYear();
        $institutions = auth()->user()->accessibleInstitutions()->get();
        $canManageResidency = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');
        $enrollments = $student->enrollments()
            ->where('academic_year', $academicYear)
            ->whereIn('institution_id', $institutions->pluck('id'))
            ->get()
            ->keyBy('institution_id');

        return view('students.institutions', compact(
            'student',
            'academicYear',
            'institutions',
            'enrollments',
            'canManageResidency'
        ));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudent($student);
        $canManageResidency = auth()->user()->hasRole('admin')
            || auth()->user()->canAccessInstitution('ponpes');

        $data = $request->validate([
            'residency_status' => [$canManageResidency ? 'required' : 'nullable', 'in:mukim,non_mukim'],
            'institutions' => ['required', 'array'],
            'institutions.*.active' => ['nullable', 'boolean'],
            'institutions.*.class_name' => ['nullable', 'string', 'max:50'],
            'institutions.*.level' => ['nullable', 'string', 'max:20'],
        ]);

        $academicYear = $this->currentAcademicYear();
        $availableInstitutions = auth()->user()->accessibleInstitutions()->get()->keyBy('id');

        foreach ($availableInstitutions as $institutionId => $institution) {
            $input = $data['institutions'][$institutionId] ?? [];
            $isActive = filter_var($input['active'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $isActive) {
                continue;
            }

            if ($institution->code === 'madad') {
                $level = StudentEnrollment::normalizeMadadLevel($input['level'] ?? null);

                if ($level === null) {
                    throw ValidationException::withMessages([
                        "institutions.{$institutionId}.level" => 'Pilih jenjang MADAD: Ula, Wustha, atau Ulya.',
                    ]);
                }

                $data['institutions'][$institutionId]['level'] = $level;
                continue;
            }

            if (in_array($institution->code, ['mi', 'mts', 'ma'], true)) {
                $data['institutions'][$institutionId]['level'] = $institution->code;
            }
        }

        DB::transaction(function () use ($data, $student, $academicYear, $availableInstitutions, $canManageResidency) {
            if ($canManageResidency) {
                $student->update([
                    'residency_status' => $data['residency_status'],
                ]);
            }

            foreach ($availableInstitutions as $institutionId => $institution) {
                $input = $data['institutions'][$institutionId] ?? [];
                $isActive = filter_var($input['active'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($isActive) {
                    $student->enrollments()->updateOrCreate(
                        [
                            'institution_id' => $institutionId,
                            'academic_year' => $academicYear,
                        ],
                        [
                            'class_name' => filled($input['class_name'] ?? null)
                                ? trim($input['class_name'])
                                : null,
                            'level' => filled($input['level'] ?? null)
                                ? trim($input['level'])
                                : null,
                            'is_active' => true,
                            'enrolled_at' => now()->toDateString(),
                            'left_at' => null,
                        ]
                    );
                } else {
                    $student->enrollments()
                        ->where('institution_id', $institutionId)
                        ->where('academic_year', $academicYear)
                        ->update([
                            'is_active' => false,
                            'left_at' => now()->toDateString(),
                        ]);
                }
            }
        });

        $redirectRoute = auth()->user()->canAccessStudent($student->fresh(), $academicYear)
            ? route('students.show', $student)
            : route('students.index');

        return redirect()
            ->to($redirectRoute)
            ->with('success', 'Keanggotaan lembaga dan kelas berhasil diperbarui.');
    }

    private function currentAcademicYear(): string
    {
        return now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;
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
