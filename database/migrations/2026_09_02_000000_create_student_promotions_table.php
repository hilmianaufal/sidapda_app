<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_enrollment_id')
                ->unique()
                ->constrained('student_enrollments')
                ->cascadeOnDelete();
            $table->foreignId('target_enrollment_id')
                ->nullable()
                ->constrained('student_enrollments')
                ->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_academic_year', 9);
            $table->string('to_academic_year', 9);
            $table->string('from_class', 50)->nullable();
            $table->string('to_class', 50)->nullable();
            $table->string('from_level', 20)->nullable();
            $table->string('to_level', 20)->nullable();
            $table->string('action', 30);
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index(['institution_id', 'to_academic_year']);
            $table->index(['student_id', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
    }
};
