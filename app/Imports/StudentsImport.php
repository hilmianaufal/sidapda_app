<?php

namespace App\Imports;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $updated = 0;
    public int $enrolled = 0;
    public int $enrollmentUpdated = 0;
    public int $skipped = 0;

    public function __construct(
        private Institution $institution,
        private string $academicYear,
        private bool $canManageBoarding = true,
    ) {}

    private function normalizeGender($value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'putra', 'laki-laki', 'laki laki', 'lk', 'l' => 'putra',
            'putri', 'perempuan', 'pr', 'p' => 'putri',
            default => null,
        };
    }

    private function normalizeResidency($value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'mukim', 'pondok', 'tinggal di pondok' => 'mukim',
            'non mukim', 'non_mukim', 'tidak mukim', 'pulang pergi' => 'non_mukim',
            default => null,
        };
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $nis = trim((string) ($row['nis'] ?? ''));
                $nama = trim((string) ($row['nama'] ?? ''));

                if ($nis === '' || $nama === '') {
                    $this->skipped++;
                    continue;
                }

                $student = Student::firstOrNew(['nis' => $nis]);
                $isNewStudent = ! $student->exists;
                $student->name = $nama;
                if ($isNewStudent) {
                    $student->is_active = true;
                }

                $generalClass = trim((string) ($row['kelas'] ?? $row['jenjang'] ?? ''));
                $room = trim((string) ($row['kamar'] ?? ''));
                $phone = trim((string) ($row['wa_ortu'] ?? ''));
                $gender = $this->normalizeGender($row['jenis_santri'] ?? null);
                $residency = $this->normalizeResidency($row['status_mukim'] ?? null);

                if ($this->canManageBoarding && $generalClass !== '') {
                    $student->kelas = $generalClass;
                }
                if ($this->canManageBoarding && $room !== '') {
                    $student->kamar = $room;
                }
                if ($phone !== '') {
                    $student->parent_phone = $phone;
                }
                if ($gender !== null) {
                    $student->gender = $gender;
                }
                if ($this->canManageBoarding && $residency !== null) {
                    $student->residency_status = $residency;
                } elseif ($isNewStudent) {
                    $student->residency_status = $this->canManageBoarding ? 'mukim' : 'non_mukim';
                }

                $student->save();

                if ($isNewStudent) {
                    $this->created++;
                } else {
                    $this->updated++;
                }

                $enrollment = $student->enrollments()->firstOrNew([
                    'institution_id' => $this->institution->id,
                    'academic_year' => $this->academicYear,
                ]);
                $isNewEnrollment = ! $enrollment->exists;

                $institutionClass = trim((string) ($row['kelas_lembaga'] ?? $generalClass));
                $institutionLevel = trim((string) ($row['jenjang_lembaga'] ?? ''));

                if ($this->institution->code === 'madad') {
                    $institutionLevel = StudentEnrollment::normalizeMadadLevel($institutionLevel) ?? '';
                } elseif (in_array($this->institution->code, ['mi', 'mts', 'ma'], true)) {
                    $institutionLevel = $this->institution->code;
                }

                $enrollment->fill([
                    'class_name' => $institutionClass !== '' ? $institutionClass : null,
                    'level' => $institutionLevel !== '' ? $institutionLevel : null,
                    'is_active' => true,
                    'enrolled_at' => $enrollment->enrolled_at ?: now()->toDateString(),
                    'left_at' => null,
                ]);
                $enrollment->save();

                if ($isNewEnrollment) {
                    $this->enrolled++;
                } else {
                    $this->enrollmentUpdated++;
                }
            }
        });
    }
}
