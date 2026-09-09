<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const LEGACY_CODE = 'sekolah-pagi';

    private const MAP_TABLE = 'school_split_migration_maps';

    public function up(): void
    {
        $legacy = DB::table('institutions')->where('code', self::LEGACY_CODE)->first();

        if (! $legacy) {
            $alreadySplit = DB::table('institutions')->where('code', 'mts')->exists()
                && DB::table('institutions')->where('code', 'ma')->exists();

            if ($alreadySplit) {
                return;
            }

            throw new RuntimeException('Lembaga Sekolah Pagi tidak ditemukan. Pemisahan MTs/MA dibatalkan.');
        }

        if (Schema::hasTable(self::MAP_TABLE)) {
            throw new RuntimeException('Tabel pemetaan pemisahan MTs/MA sudah ada. Periksa migrasi sebelumnya.');
        }

        Schema::create(self::MAP_TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('target_id');
            $table->timestamps();

            $table->unique(['entity_type', 'target_id'], 'school_split_map_target_unique');
        });

        try {
            DB::transaction(function () use ($legacy) {
                $now = now();
                $mtsId = (int) $legacy->id;

                DB::table('institutions')->where('id', $mtsId)->update([
                    'code' => 'mts',
                    'name' => 'Madrasah Tsanawiyah',
                    'short_name' => 'MTs',
                    'type' => 'school',
                    'description' => 'Absensi siswa, guru, dan ekstrakurikuler Madrasah Tsanawiyah.',
                    'color' => 'indigo',
                    'icon' => 'bi-mortarboard',
                    'sort_order' => 3,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);

                $maId = DB::table('institutions')->insertGetId(
                    $this->maInstitutionData($legacy, $now)
                );

                DB::table('institutions')->where('code', 'madad')->update([
                    'sort_order' => 5,
                    'updated_at' => $now,
                ]);

                $this->copyInstitutionAccess($mtsId, $maId, $now);
                $this->copyAttendanceSettings($mtsId, $maId, $now);
                $this->splitStudentEnrollments($mtsId, $maId);
                $this->splitSchoolAttendance('school_attendances', $mtsId, $maId);
                $this->splitSchoolAttendance('school_attendance_excuses', $mtsId, $maId);
                $this->splitTeachers($mtsId, $maId, $now);
                $this->splitExtracurriculars($mtsId, $maId, $now);
                $this->splitPromotions($mtsId, $maId);
                $this->splitWhatsappLogs($mtsId, $maId);
            });
        } catch (Throwable $exception) {
            Schema::dropIfExists(self::MAP_TABLE);

            throw $exception;
        }
    }

    public function down(): void
    {
        $mtsId = DB::table('institutions')->where('code', 'mts')->value('id');
        $maId = DB::table('institutions')->where('code', 'ma')->value('id');

        if (! $mtsId || ! $maId) {
            Schema::dropIfExists(self::MAP_TABLE);
            return;
        }

        DB::transaction(function () use ($mtsId, $maId) {
            $now = now();
            $maps = Schema::hasTable(self::MAP_TABLE)
                ? DB::table(self::MAP_TABLE)->get()
                : collect();

            foreach ($maps->where('entity_type', 'teacher_clone') as $map) {
                DB::table('school_teacher_attendances')
                    ->where('school_teacher_id', $map->target_id)
                    ->delete();
                DB::table('school_teacher_attendance_excuses')
                    ->where('school_teacher_id', $map->target_id)
                    ->delete();
                DB::table('school_teachers')->where('id', $map->target_id)->delete();
                DB::table('school_teachers')->where('id', $map->source_id)->update([
                    'level' => 'mts_ma',
                    'updated_at' => $now,
                ]);
                DB::table('school_teacher_attendances')
                    ->where('school_teacher_id', $map->source_id)
                    ->update(['level_snapshot' => 'mts_ma']);
                DB::table('school_teacher_attendance_excuses')
                    ->where('school_teacher_id', $map->source_id)
                    ->update(['level_snapshot' => 'mts_ma']);
            }

            foreach ($maps->where('entity_type', 'extracurricular_clone') as $map) {
                DB::table('school_extracurricular_attendances')
                    ->where('school_extracurricular_id', $map->target_id)
                    ->update([
                        'institution_id' => $mtsId,
                        'school_extracurricular_id' => $map->source_id,
                    ]);
                DB::table('school_extracurricular_attendance_excuses')
                    ->where('school_extracurricular_id', $map->target_id)
                    ->update([
                        'institution_id' => $mtsId,
                        'school_extracurricular_id' => $map->source_id,
                    ]);
                DB::table('school_extracurriculars')->where('id', $map->target_id)->delete();
                DB::table('school_extracurriculars')->where('id', $map->source_id)->update([
                    'level' => 'mts_ma',
                    'updated_at' => $now,
                ]);
            }

            $this->moveInstitutionRows('school_teacher_attendances', $maId, $mtsId);
            $this->moveInstitutionRows('school_teacher_attendance_excuses', $maId, $mtsId);
            $this->moveInstitutionRows('school_teachers', $maId, $mtsId);
            $this->moveInstitutionRows('school_extracurricular_attendances', $maId, $mtsId);
            $this->moveInstitutionRows('school_extracurricular_attendance_excuses', $maId, $mtsId);
            $this->moveInstitutionRows('school_extracurriculars', $maId, $mtsId);
            $this->moveInstitutionRows('school_attendances', $maId, $mtsId);
            $this->moveInstitutionRows('school_attendance_excuses', $maId, $mtsId);
            $this->moveInstitutionRows('student_promotions', $maId, $mtsId);
            $this->moveInstitutionRows('student_enrollments', $maId, $mtsId);

            if (Schema::hasTable('institution_whatsapp_logs')) {
                $this->moveInstitutionRows('institution_whatsapp_logs', $maId, $mtsId);
            }

            $maAccess = DB::table('institution_user')->where('institution_id', $maId)->get();
            foreach ($maAccess as $access) {
                DB::table('institution_user')->updateOrInsert(
                    ['institution_id' => $mtsId, 'user_id' => $access->user_id],
                    [
                        'role' => $access->role,
                        'is_active' => $access->is_active,
                        'updated_at' => $now,
                        'created_at' => $access->created_at ?: $now,
                    ]
                );
            }

            DB::table('institution_user')->where('institution_id', $maId)->delete();
            DB::table('school_attendance_settings')->where('institution_id', $maId)->delete();
            DB::table('institutions')->where('id', $maId)->delete();

            DB::table('institutions')->where('id', $mtsId)->update([
                'code' => self::LEGACY_CODE,
                'name' => 'Sekolah Pagi MTs dan MA',
                'short_name' => 'MTs & MA',
                'type' => 'school',
                'description' => 'Absensi siswa dan guru MTs serta MA.',
                'color' => 'indigo',
                'icon' => 'bi-mortarboard',
                'sort_order' => 3,
                'is_active' => true,
                'updated_at' => $now,
            ]);

            DB::table('institutions')->where('code', 'madad')->update([
                'sort_order' => 4,
                'updated_at' => $now,
            ]);
        });

        Schema::dropIfExists(self::MAP_TABLE);
    }

    private function maInstitutionData(object $legacy, mixed $now): array
    {
        $data = [
            'code' => 'ma',
            'name' => 'Madrasah Aliyah',
            'short_name' => 'MA',
            'type' => 'school',
            'description' => 'Absensi siswa, guru, dan ekstrakurikuler Madrasah Aliyah.',
            'color' => 'rose',
            'icon' => 'bi-mortarboard-fill',
            'sort_order' => 4,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $copyColumns = [
            'address',
            'phone',
            'email',
            'website',
            'leader_name',
            'fonnte_enabled',
            'fonnte_token',
            'fonnte_notify_attendance',
            'fonnte_notify_excuse',
            'fonnte_notify_boarding',
            'fonnte_message_footer',
            'fonnte_device_number',
            'fonnte_device_status',
            'fonnte_last_tested_at',
        ];

        $availableColumns = Schema::getColumnListing('institutions');
        foreach ($copyColumns as $column) {
            if (in_array($column, $availableColumns, true)) {
                $data[$column] = $legacy->{$column} ?? null;
            }
        }

        return $data;
    }

    private function copyInstitutionAccess(int $mtsId, int $maId, mixed $now): void
    {
        $accessRows = DB::table('institution_user')->where('institution_id', $mtsId)->get();

        foreach ($accessRows as $access) {
            DB::table('institution_user')->updateOrInsert(
                ['institution_id' => $maId, 'user_id' => $access->user_id],
                [
                    'role' => $access->role,
                    'is_active' => $access->is_active,
                    'created_at' => $access->created_at ?: $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function copyAttendanceSettings(int $mtsId, int $maId, mixed $now): void
    {
        $settings = DB::table('school_attendance_settings')
            ->where('institution_id', $mtsId)
            ->first();

        if (! $settings) {
            throw new RuntimeException('Pengaturan waktu Sekolah Pagi tidak ditemukan.');
        }

        $data = (array) $settings;
        unset($data['id']);
        $data['institution_id'] = $maId;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        DB::table('school_attendance_settings')->insert($data);
    }

    private function splitStudentEnrollments(int $mtsId, int $maId): void
    {
        $rows = DB::table('student_enrollments')
            ->where('institution_id', $mtsId)
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $targetCode = $this->classifyLevel($row->level, $row->class_name);

            if ($targetCode === null) {
                throw new RuntimeException(
                    'Keanggotaan siswa ID '.$row->student_id.' (data ID '.$row->id.') belum dapat '
                    .'ditentukan sebagai MTs atau MA. Lengkapi jenjang atau kelasnya terlebih dahulu.'
                );
            }

            $targetId = $targetCode === 'ma' ? $maId : $mtsId;

            DB::table('student_enrollments')->where('id', $row->id)->update([
                'institution_id' => $targetId,
                'level' => $targetId === $maId ? 'ma' : 'mts',
            ]);
        }
    }

    private function splitSchoolAttendance(string $table, int $mtsId, int $maId): void
    {
        $rows = DB::table($table)->where('institution_id', $mtsId)->orderBy('id')->get();

        foreach ($rows as $row) {
            $targetId = $this->targetInstitutionForStudentRow($row, $mtsId, $maId);

            DB::table($table)->where('id', $row->id)->update([
                'institution_id' => $targetId,
                'level_snapshot' => $targetId === $maId ? 'ma' : 'mts',
            ]);
        }
    }

    private function splitTeachers(int $mtsId, int $maId, mixed $now): void
    {
        $teachers = DB::table('school_teachers')
            ->where('institution_id', $mtsId)
            ->orderBy('id')
            ->get();

        foreach ($teachers as $teacher) {
            $level = $this->normalizeLevel($teacher->level);

            if ($level === null) {
                throw new RuntimeException(
                    'Jenjang guru ID '.$teacher->id.' tidak dikenali sebagai MTs, MA, atau keduanya.'
                );
            }

            if ($level === 'ma') {
                DB::table('school_teachers')->where('id', $teacher->id)->update([
                    'institution_id' => $maId,
                    'level' => 'ma',
                    'updated_at' => $now,
                ]);
                $this->moveTeacherHistory($teacher->id, $maId, 'ma');
                continue;
            }

            if ($level === 'both') {
                $clone = (array) $teacher;
                unset($clone['id']);
                $clone['institution_id'] = $maId;
                $clone['level'] = 'ma';
                $clone['qr_token'] = (string) Str::uuid();
                $clone['created_at'] = $now;
                $clone['updated_at'] = $now;
                $cloneId = DB::table('school_teachers')->insertGetId($clone);

                DB::table(self::MAP_TABLE)->insert([
                    'entity_type' => 'teacher_clone',
                    'source_id' => $teacher->id,
                    'target_id' => $cloneId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->cloneTeacherHistory($teacher->id, $cloneId, $maId, $now);
            }

            DB::table('school_teachers')->where('id', $teacher->id)->update([
                'level' => 'mts',
                'updated_at' => $now,
            ]);
            $this->moveTeacherHistory($teacher->id, $mtsId, 'mts');
        }
    }

    private function moveTeacherHistory(int $teacherId, int $institutionId, string $level): void
    {
        foreach (['school_teacher_attendances', 'school_teacher_attendance_excuses'] as $table) {
            DB::table($table)->where('school_teacher_id', $teacherId)->update([
                'institution_id' => $institutionId,
                'level_snapshot' => $level,
            ]);
        }
    }

    private function cloneTeacherHistory(
        int $sourceTeacherId,
        int $targetTeacherId,
        int $maId,
        mixed $now
    ): void {
        foreach (['school_teacher_attendances', 'school_teacher_attendance_excuses'] as $table) {
            $rows = DB::table($table)->where('school_teacher_id', $sourceTeacherId)->get();

            foreach ($rows as $row) {
                $data = (array) $row;
                unset($data['id']);
                $data['institution_id'] = $maId;
                $data['school_teacher_id'] = $targetTeacherId;
                $data['level_snapshot'] = 'ma';
                $data['created_at'] = $row->created_at ?: $now;
                $data['updated_at'] = $row->updated_at ?: $now;
                DB::table($table)->insert($data);
            }
        }
    }

    private function splitExtracurriculars(int $mtsId, int $maId, mixed $now): void
    {
        $activities = DB::table('school_extracurriculars')
            ->where('institution_id', $mtsId)
            ->orderBy('id')
            ->get();

        foreach ($activities as $activity) {
            $level = $this->normalizeLevel($activity->level);

            if ($level === null) {
                throw new RuntimeException(
                    'Jenjang ekstrakurikuler ID '.$activity->id.' tidak dikenali sebagai MTs, MA, atau keduanya.'
                );
            }

            if ($level === 'ma') {
                DB::table('school_extracurriculars')->where('id', $activity->id)->update([
                    'institution_id' => $maId,
                    'level' => 'ma',
                    'updated_at' => $now,
                ]);
                $this->moveExtracurricularHistory($activity->id, $activity->id, $maId, 'ma');
                continue;
            }

            if ($level === 'both') {
                $clone = (array) $activity;
                unset($clone['id']);
                $clone['institution_id'] = $maId;
                $clone['level'] = 'ma';
                $clone['created_at'] = $now;
                $clone['updated_at'] = $now;
                $cloneId = DB::table('school_extracurriculars')->insertGetId($clone);

                DB::table(self::MAP_TABLE)->insert([
                    'entity_type' => 'extracurricular_clone',
                    'source_id' => $activity->id,
                    'target_id' => $cloneId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->splitCombinedExtracurricularHistory($activity->id, $cloneId, $mtsId, $maId);
            }

            DB::table('school_extracurriculars')->where('id', $activity->id)->update([
                'level' => 'mts',
                'updated_at' => $now,
            ]);
        }
    }

    private function moveExtracurricularHistory(
        int $sourceActivityId,
        int $targetActivityId,
        int $targetInstitutionId,
        string $targetLevel
    ): void {
        foreach (['school_extracurricular_attendances', 'school_extracurricular_attendance_excuses'] as $table) {
            $rows = DB::table($table)
                ->where('school_extracurricular_id', $sourceActivityId)
                ->get();

            foreach ($rows as $row) {
                DB::table($table)->where('id', $row->id)->update([
                    'institution_id' => $targetInstitutionId,
                    'school_extracurricular_id' => $targetActivityId,
                    'level_snapshot' => $targetLevel,
                ]);
            }
        }
    }

    private function splitCombinedExtracurricularHistory(
        int $sourceActivityId,
        int $maActivityId,
        int $mtsId,
        int $maId
    ): void {
        foreach (['school_extracurricular_attendances', 'school_extracurricular_attendance_excuses'] as $table) {
            $rows = DB::table($table)
                ->where('school_extracurricular_id', $sourceActivityId)
                ->get();

            foreach ($rows as $row) {
                $targetId = $this->targetInstitutionForStudentRow($row, $mtsId, $maId);

                DB::table($table)->where('id', $row->id)->update([
                    'institution_id' => $targetId,
                    'school_extracurricular_id' => $targetId === $maId
                        ? $maActivityId
                        : $sourceActivityId,
                    'level_snapshot' => $targetId === $maId ? 'ma' : 'mts',
                ]);
            }
        }
    }

    private function splitPromotions(int $mtsId, int $maId): void
    {
        $promotions = DB::table('student_promotions')
            ->where('institution_id', $mtsId)
            ->get();

        foreach ($promotions as $promotion) {
            $sourceInstitutionId = DB::table('student_enrollments')
                ->where('id', $promotion->source_enrollment_id)
                ->value('institution_id');

            if ((int) $sourceInstitutionId === $maId) {
                DB::table('student_promotions')->where('id', $promotion->id)->update([
                    'institution_id' => $maId,
                ]);
            }
        }
    }

    private function splitWhatsappLogs(int $mtsId, int $maId): void
    {
        if (! Schema::hasTable('institution_whatsapp_logs')) {
            return;
        }

        $logs = DB::table('institution_whatsapp_logs')
            ->where('institution_id', $mtsId)
            ->whereNotNull('student_id')
            ->get();

        foreach ($logs as $log) {
            $isMa = DB::table('student_enrollments')
                ->where('student_id', $log->student_id)
                ->where('institution_id', $maId)
                ->exists();

            if ($isMa) {
                DB::table('institution_whatsapp_logs')->where('id', $log->id)->update([
                    'institution_id' => $maId,
                ]);
            }
        }
    }

    private function targetInstitutionForStudentRow(object $row, int $mtsId, int $maId): int
    {
        $classification = $this->classifyLevel(
            $row->level_snapshot ?? null,
            $row->class_name_snapshot ?? null
        );

        if ($classification === 'ma') {
            return $maId;
        }

        if ($classification === 'mts') {
            return $mtsId;
        }

        $enrollmentInstitutionId = DB::table('student_enrollments')
            ->where('student_id', $row->student_id)
            ->where('academic_year', $row->academic_year)
            ->whereIn('institution_id', [$mtsId, $maId])
            ->value('institution_id');

        if ($enrollmentInstitutionId !== null) {
            return (int) $enrollmentInstitutionId;
        }

        throw new RuntimeException(
            'Riwayat siswa ID '.$row->student_id.' (data ID '.$row->id.', tahun '
            .$row->academic_year.') belum dapat ditentukan sebagai MTs atau MA.'
        );
    }

    private function classifyLevel(?string $level, ?string $className): ?string
    {
        $normalizedLevel = $this->normalizeLevel($level);

        if (in_array($normalizedLevel, ['mts', 'ma'], true)) {
            return $normalizedLevel;
        }

        $className = mb_strtoupper(trim((string) $className));

        if (preg_match('/(?:^|\D)(10|11|12)(?:\D|$)/', $className)
            || preg_match('/\b(XII|XI|X)\b/u', $className)) {
            return 'ma';
        }

        if (preg_match('/(?:^|\D)(7|8|9)(?:\D|$)/', $className)
            || preg_match('/\b(IX|VIII|VII)\b/u', $className)) {
            return 'mts';
        }

        return null;
    }

    private function normalizeLevel(?string $level): ?string
    {
        $level = mb_strtolower(trim((string) $level));
        $level = preg_replace('/\s+/', ' ', $level) ?: $level;

        return match ($level) {
            'mts', 'madrasah tsanawiyah', 'tsanawiyah' => 'mts',
            'ma', 'aliyah', 'madrasah aliyah' => 'ma',
            'mts_ma', 'mts & ma', 'mts dan ma', 'mts/ma', 'mts-ma', 'keduanya' => 'both',
            default => null,
        };
    }

    private function moveInstitutionRows(string $table, int $fromId, int $toId): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->where('institution_id', $fromId)->update([
                'institution_id' => $toId,
            ]);
        }
    }
};
