<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_teacher_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_teacher_id')->constrained('school_teachers')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('academic_year', 9);
            $table->string('teacher_code_snapshot', 50);
            $table->string('level_snapshot', 20);
            $table->timestamp('check_in_at')->nullable();
            $table->string('check_in_status', 30)->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('check_out_status', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['institution_id', 'school_teacher_id', 'attendance_date'],
                'school_teacher_attendance_day_unique'
            );
            $table->index(
                ['institution_id', 'attendance_date'],
                'school_teacher_attendance_institution_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_teacher_attendances');
    }
};
