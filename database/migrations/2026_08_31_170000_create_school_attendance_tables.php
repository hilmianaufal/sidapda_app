<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
        Schema::create('school_attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $table->time('check_in_open')->default('05:00:00');
            $table->time('check_in_time')->default('07:20:00');
            $table->time('check_in_deadline')->default('09:00:00');
            $table->time('check_out_open')->default('09:01:00');
            $table->time('check_out_time')->default('13:10:00');
            $table->time('check_out_deadline')->default('14:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('school_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('academic_year', 9);
            $table->string('class_name_snapshot', 50)->nullable();
            $table->string('level_snapshot', 20)->nullable();
            $table->timestamp('check_in_at')->nullable();
            $table->string('check_in_status', 30)->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('check_out_status', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['institution_id', 'student_id', 'attendance_date'],
                'school_attendance_student_day_unique'
            );
            $table->index(
                ['institution_id', 'attendance_date'],
                'school_attendance_institution_date_index'
            );
        });

        $institutionId = DB::table('institutions')
            ->where('code', 'sekolah-pagi')
            ->value('id');

        if ($institutionId) {
            DB::table('school_attendance_settings')->insert([
                'institution_id' => $institutionId,
                'check_in_open' => '05:00:00',
                'check_in_time' => '07:20:00',
                'check_in_deadline' => '09:00:00',
                'check_out_open' => '09:01:00',
                'check_out_time' => '13:10:00',
                'check_out_deadline' => '14:00:00',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        } catch (\Throwable $exception) {
            Schema::dropIfExists('school_attendances');
            Schema::dropIfExists('school_attendance_settings');

            throw $exception;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_attendances');
        Schema::dropIfExists('school_attendance_settings');
    }
};
