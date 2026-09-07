<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('teacher_code', 50);
            $table->string('name', 120);
            $table->string('gender', 10)->nullable();
            $table->string('level', 20);
            $table->string('phone', 30)->nullable();
            $table->uuid('qr_token')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['institution_id', 'teacher_code'],
                'school_teacher_institution_code_unique'
            );
            $table->index(
                ['institution_id', 'level', 'is_active'],
                'school_teacher_institution_level_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_teachers');
    }
};
