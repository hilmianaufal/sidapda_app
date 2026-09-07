<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolTeacherAttendanceExcuse extends Model
{
    protected $fillable = [
        'institution_id',
        'school_teacher_id',
        'attendance_date',
        'academic_year',
        'teacher_code_snapshot',
        'level_snapshot',
        'status',
        'notes',
        'attachment_path',
        'attachment_original_name',
        'attachment_mime',
        'attachment_size',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'attachment_size' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(SchoolTeacher::class, 'school_teacher_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
