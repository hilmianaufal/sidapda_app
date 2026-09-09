<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolTeacherAttendance extends Model
{
    protected $fillable = [
        'institution_id',
        'school_teacher_id',
        'attendance_date',
        'academic_year',
        'teacher_code_snapshot',
        'level_snapshot',
        'check_in_at',
        'check_in_status',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy_meters',
        'check_in_distance_meters',
        'check_out_at',
        'check_out_status',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy_meters',
        'check_out_distance_meters',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy_meters' => 'float',
            'check_in_distance_meters' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy_meters' => 'float',
            'check_out_distance_meters' => 'float',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
