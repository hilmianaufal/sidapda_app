<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolExtracurricular extends Model
{
    protected $fillable = [
        'institution_id',
        'code',
        'name',
        'level',
        'schedule_day',
        'start_time',
        'end_time',
        'late_minutes',
        'coach_name',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'schedule_day' => 'integer',
            'late_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SchoolExtracurricularAttendance::class);
    }

    public function attendanceExcuses(): HasMany
    {
        return $this->hasMany(SchoolExtracurricularAttendanceExcuse::class);
    }

    public function dayLabel(): string
    {
        return match ($this->schedule_day) {
            0 => 'Ahad',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => '-',
        };
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            'mts' => 'MTs',
            'ma' => 'MA',
            'mts_ma' => 'MTs & MA',
            default => '-',
        };
    }

    public function startTimeLabel(): string
    {
        return substr((string) $this->start_time, 0, 5);
    }

    public function endTimeLabel(): string
    {
        return substr((string) $this->end_time, 0, 5);
    }
}
