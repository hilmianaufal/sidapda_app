<?php

namespace App\Services;

use App\Models\Institution;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPromotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StudentPromotionService
{
    public function nextAcademicYear(string $academicYear): string
    {
        [$start, $end] = $this->parseAcademicYear($academicYear);

        return $end.'/'.($end + 1);
    }

    public function previousAcademicYear(string $academicYear): string
    {
        [$start] = $this->parseAcademicYear($academicYear);

        return ($start - 1).'/'.$start;
    }

    public function previewGroups(Institution $institution, string $fromAcademicYear): Collection
    {
        $toAcademicYear = $this->nextAcademicYear($fromAcademicYear);

        return StudentEnrollment::query()
            ->where('institution_id', $institution->id)
            ->where('academic_year', $fromAcademicYear)
            ->where('is_active', true)
            ->whereHas('student', fn (Builder $query) => $query->where('is_active', true))
            ->selectRaw('class_name, level, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM student_promotions WHERE student_promotions.source_enrollment_id = student_enrollments.id) THEN 1 ELSE 0 END) as processed')
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM student_enrollments AS target WHERE target.institution_id = student_enrollments.institution_id AND target.student_id = student_enrollments.student_id AND target.academic_year = ?) THEN 1 ELSE 0 END) as target_exists', [$toAcademicYear])
            ->groupBy('class_name', 'level')
            ->orderByRaw('class_name IS NULL, class_name')
            ->orderBy('level')
            ->get()
            ->map(function (StudentEnrollment $group) use ($institution) {
                $suggestion = $this->suggestion(
                    $institution,
                    $group->class_name,
                    $group->level
                );

                return [
                    'source_class' => $group->class_name,
                    'source_level' => $group->level,
                    'total' => (int) $group->getAttribute('total'),
                    'processed' => (int) $group->getAttribute('processed'),
                    'target_exists' => (int) $group->getAttribute('target_exists'),
                    'remaining' => max(0, (int) $group->getAttribute('total') - (int) $group->getAttribute('processed')),
                    ...$suggestion,
                ];
            });
    }

    public function suggestion(
        Institution $institution,
        ?string $sourceClass,
        ?string $sourceLevel
    ): array {
        $grade = $this->gradeNumber($sourceClass);
        $targetClass = $this->incrementClass($sourceClass);
        $normalizedLevel = mb_strtolower(trim((string) $sourceLevel));

        if ($institution->code === 'mi') {
            if ($grade !== null && $grade >= 6) {
                return $this->graduationSuggestion('Kelas akhir MI');
            }

            return $targetClass === null
                ? $this->manualSuggestion()
                : $this->promotionSuggestion($targetClass, 'MI', 'Naik satu kelas MI');
        }

        if ($institution->code === 'sekolah-pagi') {
            if ($grade !== null && $grade >= 12) {
                return $this->graduationSuggestion('Kelas akhir MA');
            }

            if ($targetClass === null) {
                return $this->manualSuggestion();
            }

            $targetLevel = match (true) {
                $grade !== null && $grade >= 9 => 'MA',
                $grade !== null => 'MTs',
                $normalizedLevel === 'ma' => 'MA',
                default => 'MTs',
            };

            return $this->promotionSuggestion(
                $targetClass,
                $targetLevel,
                $grade === 9 ? 'Naik ke jenjang MA' : 'Naik satu kelas'
            );
        }

        return $targetClass === null
            ? $this->manualSuggestion()
            : $this->promotionSuggestion(
                $targetClass,
                $sourceLevel,
                'Naik satu kelas; jenjang tetap'
            );
    }

    public function processGroups(
        Institution $institution,
        string $fromAcademicYear,
        array $groups,
        ?int $processedBy = null
    ): array {
        $toAcademicYear = $this->nextAcademicYear($fromAcademicYear);
        $result = [
            'promoted' => 0,
            'graduated' => 0,
            'already_enrolled' => 0,
            'already_processed' => 0,
        ];

        DB::transaction(function () use (
            $institution,
            $fromAcademicYear,
            $toAcademicYear,
            $groups,
            $processedBy,
            &$result
        ) {
            foreach ($groups as $group) {
                $action = $group['action'] ?? 'skip';

                if ($action === 'skip') {
                    continue;
                }

                if (! in_array($action, ['promote', 'graduate'], true)) {
                    throw new InvalidArgumentException('Aksi kenaikan kelas tidak valid.');
                }

                $targetClass = $this->cleanNullable($group['target_class'] ?? null, 50);
                $targetLevel = $this->cleanNullable($group['target_level'] ?? null, 20);

                if ($action === 'promote' && $targetClass === null) {
                    throw new InvalidArgumentException('Kelas tujuan wajib diisi untuk siswa yang naik kelas.');
                }

                $query = StudentEnrollment::query()
                    ->with('student')
                    ->where('institution_id', $institution->id)
                    ->where('academic_year', $fromAcademicYear)
                    ->where('is_active', true)
                    ->whereHas('student', fn (Builder $student) => $student->where('is_active', true));

                $this->applyNullableFilter($query, 'class_name', $group['source_class'] ?? null);
                $this->applyNullableFilter($query, 'level', $group['source_level'] ?? null);

                $enrollments = $query->lockForUpdate()->get();

                foreach ($enrollments as $source) {
                    if (StudentPromotion::where('source_enrollment_id', $source->id)->exists()) {
                        $result['already_processed']++;
                        continue;
                    }

                    $source->update([
                        'left_at' => $source->left_at ?: $this->academicYearEndDate($fromAcademicYear),
                    ]);

                    if ($action === 'graduate') {
                        StudentPromotion::create([
                            'student_id' => $source->student_id,
                            'institution_id' => $institution->id,
                            'source_enrollment_id' => $source->id,
                            'target_enrollment_id' => null,
                            'processed_by' => $processedBy,
                            'from_academic_year' => $fromAcademicYear,
                            'to_academic_year' => $toAcademicYear,
                            'from_class' => $source->class_name,
                            'to_class' => null,
                            'from_level' => $source->level,
                            'to_level' => null,
                            'action' => 'graduated',
                            'processed_at' => now(),
                        ]);

                        $result['graduated']++;
                        continue;
                    }

                    $target = StudentEnrollment::query()
                        ->where('institution_id', $institution->id)
                        ->where('student_id', $source->student_id)
                        ->where('academic_year', $toAcademicYear)
                        ->first();

                    $promotionAction = 'already_enrolled';

                    if ($target === null) {
                        $target = StudentEnrollment::create([
                            'institution_id' => $institution->id,
                            'student_id' => $source->student_id,
                            'academic_year' => $toAcademicYear,
                            'class_name' => $targetClass,
                            'level' => $targetLevel,
                            'is_active' => true,
                            'enrolled_at' => $this->academicYearStartDate($toAcademicYear),
                            'left_at' => null,
                        ]);
                        $promotionAction = 'promoted';
                    }

                    StudentPromotion::create([
                        'student_id' => $source->student_id,
                        'institution_id' => $institution->id,
                        'source_enrollment_id' => $source->id,
                        'target_enrollment_id' => $target->id,
                        'processed_by' => $processedBy,
                        'from_academic_year' => $fromAcademicYear,
                        'to_academic_year' => $toAcademicYear,
                        'from_class' => $source->class_name,
                        'to_class' => $target->class_name,
                        'from_level' => $source->level,
                        'to_level' => $target->level,
                        'action' => $promotionAction,
                        'processed_at' => now(),
                    ]);

                    if (
                        $institution->type === 'school'
                        && $toAcademicYear === Student::academicYearForDate()
                        && filled($target->class_name)
                    ) {
                        $source->student->update(['kelas' => $target->class_name]);
                    }

                    $result[$promotionAction]++;
                }
            }
        });

        return $result;
    }

    private function parseAcademicYear(string $academicYear): array
    {
        if (! preg_match('/^(\d{4})\/(\d{4})$/', trim($academicYear), $matches)) {
            throw new InvalidArgumentException('Format tahun ajaran harus seperti 2026/2027.');
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];

        if ($end !== $start + 1) {
            throw new InvalidArgumentException('Tahun ajaran harus berurutan.');
        }

        return [$start, $end];
    }

    private function incrementClass(?string $sourceClass): ?string
    {
        $sourceClass = trim((string) $sourceClass);

        if ($sourceClass === '') {
            return null;
        }

        if (! preg_match('/^(\s*(?:kelas\s*)?)(\d{1,2})(.*)$/iu', $sourceClass, $matches)) {
            if (! preg_match(
                '/^(\s*(?:kelas\s*)?)(XII|XI|IX|VIII|VII|VI|IV|III|II|X|V|I)(?=$|[\s\-\/])(.*)$/iu',
                $sourceClass,
                $matches
            )) {
                return null;
            }

            $grade = $this->romanToInteger($matches[2]);

            if ($grade === null || $grade >= 99) {
                return null;
            }

            $targetRoman = $this->integerToRoman($grade + 1);

            if (mb_strtolower($matches[2]) === $matches[2]) {
                $targetRoman = mb_strtolower($targetRoman);
            }

            return trim($matches[1].$targetRoman.$matches[3]);
        }

        $grade = (int) $matches[2];

        if ($grade < 1 || $grade >= 99) {
            return null;
        }

        return trim($matches[1].($grade + 1).$matches[3]);
    }

    private function gradeNumber(?string $sourceClass): ?int
    {
        $sourceClass = trim((string) $sourceClass);

        if (preg_match('/^(?:kelas\s*)?(\d{1,2})(?:\D|$)/iu', $sourceClass, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match(
            '/^(?:kelas\s*)?(XII|XI|IX|VIII|VII|VI|IV|III|II|X|V|I)(?=$|[\s\-\/])/iu',
            $sourceClass,
            $matches
        )) {
            return $this->romanToInteger($matches[1]);
        }

        return null;
    }

    private function romanToInteger(string $roman): ?int
    {
        $roman = mb_strtoupper($roman);
        $values = ['I' => 1, 'V' => 5, 'X' => 10, 'L' => 50, 'C' => 100];
        $total = 0;
        $previous = 0;

        for ($index = mb_strlen($roman) - 1; $index >= 0; $index--) {
            $character = mb_substr($roman, $index, 1);
            $value = $values[$character] ?? null;

            if ($value === null) {
                return null;
            }

            if ($value < $previous) {
                $total -= $value;
            } else {
                $total += $value;
                $previous = $value;
            }
        }

        return $total;
    }

    private function integerToRoman(int $number): string
    {
        $map = [
            90 => 'XC',
            50 => 'L',
            40 => 'XL',
            10 => 'X',
            9 => 'IX',
            5 => 'V',
            4 => 'IV',
            1 => 'I',
        ];
        $roman = '';

        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $roman .= $symbol;
                $number -= $value;
            }
        }

        return $roman;
    }

    private function promotionSuggestion(?string $targetClass, ?string $targetLevel, string $reason): array
    {
        return [
            'action' => 'promote',
            'target_class' => $targetClass,
            'target_level' => $targetLevel,
            'reason' => $reason,
        ];
    }

    private function graduationSuggestion(string $reason): array
    {
        return [
            'action' => 'graduate',
            'target_class' => null,
            'target_level' => null,
            'reason' => $reason,
        ];
    }

    private function manualSuggestion(): array
    {
        return [
            'action' => 'skip',
            'target_class' => null,
            'target_level' => null,
            'reason' => 'Format kelas perlu ditentukan manual',
        ];
    }

    private function cleanNullable(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }

    private function applyNullableFilter(Builder $query, string $column, mixed $value): void
    {
        $value = $this->cleanNullable($value, $column === 'class_name' ? 50 : 20);

        if ($value === null) {
            $query->where(fn (Builder $nullable) => $nullable
                ->whereNull($column)
                ->orWhere($column, ''));

            return;
        }

        $query->where($column, $value);
    }

    private function academicYearStartDate(string $academicYear): string
    {
        [$start] = $this->parseAcademicYear($academicYear);

        return $start.'-07-01';
    }

    private function academicYearEndDate(string $academicYear): string
    {
        [, $end] = $this->parseAcademicYear($academicYear);

        return $end.'-06-30';
    }
}
