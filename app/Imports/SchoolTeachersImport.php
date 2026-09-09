<?php

namespace App\Imports;

use App\Models\Institution;
use App\Models\SchoolTeacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SchoolTeachersImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    public function __construct(
        private readonly Institution $institution
    ) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $code = Str::upper($this->firstValue($row, ['kode_guru', 'nip', 'nik']));
                $name = $this->firstValue($row, ['nama', 'nama_guru']);
                $gender = $this->normalizeGender($this->firstValue($row, ['jenis_kelamin', 'gender', 'jk']));
                $level = $this->normalizeLevel($this->firstValue($row, ['jenjang', 'unit', 'lembaga']));
                $phone = $this->firstValue($row, ['no_hp', 'nomor_hp', 'telepon', 'wa']);
                $active = $this->normalizeActive($this->firstValue($row, ['status', 'aktif']));

                if (
                    $code === '' ||
                    $name === '' ||
                    $level === null ||
                    !in_array($level, $this->allowedLevels(), true) ||
                    mb_strlen($code) > 50 ||
                    mb_strlen($name) > 120 ||
                    mb_strlen($phone) > 30
                ) {
                    $this->skipped++;
                    continue;
                }

                $teacher = SchoolTeacher::query()
                    ->where('institution_id', $this->institution->id)
                    ->where('teacher_code', $code)
                    ->first();

                $values = [
                    'name' => $name,
                    'gender' => $gender,
                    'level' => $level,
                    'phone' => $phone !== '' ? $phone : null,
                    'is_active' => $active,
                ];

                if ($teacher) {
                    $teacher->update($values);
                    $this->updated++;
                    continue;
                }

                SchoolTeacher::create(array_merge($values, [
                    'institution_id' => $this->institution->id,
                    'teacher_code' => $code,
                ]));
                $this->created++;
            }
        });
    }

    private function firstValue(Collection $row, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($row->get($key) ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function normalizeGender(string $value): ?string
    {
        $value = Str::lower(trim($value));

        return match ($value) {
            'l', 'lk', 'laki-laki', 'laki laki', 'pria', 'putra' => 'putra',
            'p', 'pr', 'perempuan', 'wanita', 'putri' => 'putri',
            default => null,
        };
    }

    private function normalizeLevel(string $value): ?string
    {
        $value = Str::lower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));

        return match ($value) {
            'mi', 'madrasah ibtidaiyah' => 'mi',
            'mts', 'madrasah tsanawiyah' => 'mts',
            'ma', 'madrasah aliyah' => 'ma',
            'mts & ma', 'mts dan ma', 'mts/ma', 'mts-ma', 'keduanya' => 'mts_ma',
            default => null,
        };
    }

    private function allowedLevels(): array
    {
        return match ($this->institution->code) {
            'mi' => ['mi'],
            'mts' => ['mts'],
            'ma' => ['ma'],
            default => [],
        };
    }

    private function normalizeActive(string $value): bool
    {
        $value = Str::lower(trim($value));

        return !in_array($value, ['0', 'tidak', 'nonaktif', 'non-aktif', 'off', 'inactive'], true);
    }
}
