<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::create('school_teacher_attendance_excuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
                $table->foreignId('school_teacher_id')->constrained('school_teachers')->cascadeOnDelete();
                $table->date('attendance_date');
                $table->string('academic_year', 9);
                $table->string('teacher_code_snapshot', 50);
                $table->string('level_snapshot', 20);
                $table->string('status', 20);
                $table->text('notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->string('attachment_original_name')->nullable();
                $table->string('attachment_mime', 100)->nullable();
                $table->unsignedBigInteger('attachment_size')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['institution_id', 'school_teacher_id', 'attendance_date'],
                    'school_teacher_excuse_teacher_day_unique'
                );
                $table->index(
                    ['institution_id', 'attendance_date', 'status'],
                    'school_teacher_excuse_institution_date_status_index'
                );
            });
        } catch (\Throwable $exception) {
            Schema::dropIfExists('school_teacher_attendance_excuses');

            throw $exception;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_teacher_attendance_excuses');
    }
};
