<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('short_name', 50);
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->string('color', 30)->default('emerald');
            $table->string('icon', 50)->default('bi-building');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year', 9);
            $table->string('class_name', 50)->nullable();
            $table->string('level', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('enrolled_at')->nullable();
            $table->date('left_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['institution_id', 'student_id', 'academic_year'],
                'student_enrollment_unique'
            );
            $table->index(['institution_id', 'academic_year', 'is_active']);
            $table->index(['student_id', 'is_active']);
        });

        Schema::create('institution_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institution_id', 'user_id']);
        });

        $now = now();
        $institutions = [
            [
                'code' => 'ponpes',
                'name' => 'Pondok Pesantren Darussalam',
                'short_name' => 'PONPES',
                'type' => 'boarding',
                'description' => 'Absensi salat dan kegiatan kepesantrenan.',
                'color' => 'emerald',
                'icon' => 'bi-moon-stars',
                'sort_order' => 1,
            ],
            [
                'code' => 'mi',
                'name' => 'Madrasah Ibtidaiyah',
                'short_name' => 'MI',
                'type' => 'school',
                'description' => 'Absensi siswa dan guru MI.',
                'color' => 'sky',
                'icon' => 'bi-backpack',
                'sort_order' => 2,
            ],
            [
                'code' => 'sekolah-pagi',
                'name' => 'Sekolah Pagi MTs dan MA',
                'short_name' => 'MTs & MA',
                'type' => 'school',
                'description' => 'Absensi siswa dan guru MTs serta MA.',
                'color' => 'indigo',
                'icon' => 'bi-mortarboard',
                'sort_order' => 3,
            ],
            [
                'code' => 'madad',
                'name' => 'Madrasah Diniyah Assuyuthiyah Darussalam',
                'short_name' => 'MADAD',
                'type' => 'diniyah',
                'description' => 'Absensi kegiatan Madrasah Diniyah.',
                'color' => 'amber',
                'icon' => 'bi-book',
                'sort_order' => 4,
            ],
        ];

        foreach ($institutions as $institution) {
            DB::table('institutions')->insert(array_merge($institution, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $ponpesId = DB::table('institutions')->where('code', 'ponpes')->value('id');
        $academicYear = now()->month >= 7
            ? now()->year.'/'.(now()->year + 1)
            : (now()->year - 1).'/'.now()->year;

        DB::table('students')
            ->select(['id', 'kelas', 'is_active'])
            ->orderBy('id')
            ->chunkById(500, function ($students) use ($ponpesId, $academicYear, $now) {
                $rows = $students->map(fn ($student) => [
                    'institution_id' => $ponpesId,
                    'student_id' => $student->id,
                    'academic_year' => $academicYear,
                    'class_name' => $student->kelas,
                    'level' => null,
                    'is_active' => (bool) $student->is_active,
                    'enrolled_at' => $now->toDateString(),
                    'left_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                if ($rows !== []) {
                    DB::table('student_enrollments')->insert($rows);
                }
            });

        $institutionIds = DB::table('institutions')->pluck('id');

        DB::table('users')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($institutionIds, $now) {
                $rows = [];

                foreach ($users as $user) {
                    foreach ($institutionIds as $institutionId) {
                        $rows[] = [
                            'institution_id' => $institutionId,
                            'user_id' => $user->id,
                            'role' => null,
                            'is_active' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('institution_user')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_user');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('institutions');
    }
};
