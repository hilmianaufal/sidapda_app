<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolExtracurricularAttendance extends Model
{
    protected $fillable = [
        'institution_id',
        'school_extracurricular_id',
        'student_id',
        'attendance_date',
        'academic_year',
        'nis_snapshot',
        'level_snapshot',
        'class_name_snapshot',
        'scanned_at',
        'status',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'scanned_at' => 'datetime',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(SchoolExtracurricular::class, 'school_extracurricular_id');
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
