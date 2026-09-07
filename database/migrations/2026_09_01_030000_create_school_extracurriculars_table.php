<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_extracurriculars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->string('code', 80);
            $table->string('name', 120);
            $table->string('level', 20);
            $table->unsignedTinyInteger('schedule_day');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->string('coach_name', 120)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institution_id', 'code'], 'school_extracurriculars_institution_code_unique');
            $table->index(['institution_id', 'is_active', 'schedule_day'], 'school_extracurriculars_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_extracurriculars');
    }
};
