<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAttendanceExcuse extends Model
{
    protected $fillable = [
        'institution_id',
        'student_id',
        'attendance_date',
        'academic_year',
        'class_name_snapshot',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
