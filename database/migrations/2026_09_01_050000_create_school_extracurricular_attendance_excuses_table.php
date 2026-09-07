<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_extracurricular_attendance_excuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id');
            $table->foreignId('school_extracurricular_id');
            $table->foreignId('student_id');
            $table->date('attendance_date');
            $table->string('academic_year', 9);
            $table->string('nis_snapshot', 50);
            $table->string('level_snapshot', 20)->nullable();
            $table->string('class_name_snapshot', 50)->nullable();
            $table->string('status', 20);
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();
            $table->foreignId('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('institution_id', 'school_extra_excuse_institution_fk')
                ->references('id')->on('institutions')->cascadeOnDelete();
            $table->foreign('school_extracurricular_id', 'school_extra_excuse_activity_fk')
                ->references('id')->on('school_extracurriculars')->restrictOnDelete();
            $table->foreign('student_id', 'school_extra_excuse_student_fk')
                ->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('recorded_by', 'school_extra_excuse_user_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['school_extracurricular_id', 'student_id', 'attendance_date'],
                'school_extra_excuse_student_day_unique'
            );
            $table->index(
                ['institution_id', 'attendance_date', 'school_extracurricular_id', 'status'],
                'school_extra_excuse_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_extracurricular_attendance_excuses');
    }
};
