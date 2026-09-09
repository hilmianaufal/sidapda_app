<?php

namespace App\Console\Commands;

use App\Models\Institution;
use App\Models\Student;
use App\Services\StudentPromotionService;
use Illuminate\Console\Command;
use Throwable;

class PromoteStudents extends Command
{
    protected $signature = 'students:promote
        {--institution= : Kode lembaga tertentu, misalnya mts atau ma}
        {--from= : Tahun ajaran asal, misalnya 2026/2027}
        {--dry-run : Tampilkan rencana tanpa mengubah data}';

    protected $description = 'Naikkan kelas siswa otomatis ke tahun ajaran berikutnya';

    public function handle(StudentPromotionService $promotionService): int
    {
        $currentAcademicYear = Student::academicYearForDate();

        try {
            $fromAcademicYear = filled($this->option('from'))
                ? (string) $this->option('from')
                : $promotionService->previousAcademicYear($currentAcademicYear);
            $toAcademicYear = $promotionService->nextAcademicYear($fromAcademicYear);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $institutions = Institution::query()
            ->where('is_active', true)
            ->when(
                filled($this->option('institution')),
                fn ($query) => $query->where('code', $this->option('institution'))
            )
            ->orderBy('sort_order')
            ->get();

        if ($institutions->isEmpty()) {
            $this->warn('Tidak ada lembaga aktif yang sesuai.');

            return self::SUCCESS;
        }

        $this->info("Rencana kenaikan kelas {$fromAcademicYear} ke {$toAcademicYear}");
        $grandTotal = [
            'promoted' => 0,
            'graduated' => 0,
            'already_enrolled' => 0,
            'already_processed' => 0,
        ];

        foreach ($institutions as $institution) {
            $groups = $promotionService->previewGroups($institution, $fromAcademicYear);
            $automaticGroups = $groups
                ->filter(fn (array $group) => $group['remaining'] > 0 && $group['action'] !== 'skip')
                ->map(fn (array $group) => [
                    'source_class' => $group['source_class'],
                    'source_level' => $group['source_level'],
                    'action' => $group['action'],
                    'target_class' => $group['target_class'],
                    'target_level' => $group['target_level'],
                ])
                ->values()
                ->all();

            $manualCount = $groups
                ->where('action', 'skip')
                ->sum('remaining');

            if ($automaticGroups === []) {
                $this->line("- {$institution->short_name}: tidak ada data baru; perlu manual {$manualCount} siswa.");
                continue;
            }

            if ($this->option('dry-run')) {
                $planned = collect($automaticGroups)->sum(function (array $group) use ($groups) {
                    return $groups
                        ->first(fn (array $preview) =>
                            $preview['source_class'] === $group['source_class']
                            && $preview['source_level'] === $group['source_level']
                        )['remaining'] ?? 0;
                });
                $this->line("- {$institution->short_name}: siap otomatis {$planned} siswa; perlu manual {$manualCount} siswa.");
                continue;
            }

            $result = $promotionService->processGroups(
                $institution,
                $fromAcademicYear,
                $automaticGroups
            );

            foreach ($grandTotal as $key => $value) {
                $grandTotal[$key] += $result[$key];
            }

            $this->line(
                "- {$institution->short_name}: naik {$result['promoted']}, "
                ."lulus {$result['graduated']}, sudah terdaftar {$result['already_enrolled']}, "
                ."perlu manual {$manualCount}."
            );
        }

        if ($this->option('dry-run')) {
            $this->comment('Dry-run selesai. Tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        $this->info(
            "Selesai: naik {$grandTotal['promoted']}, lulus {$grandTotal['graduated']}, "
            ."sudah terdaftar {$grandTotal['already_enrolled']}, "
            ."sudah diproses {$grandTotal['already_processed']}."
        );

        return self::SUCCESS;
    }
}
