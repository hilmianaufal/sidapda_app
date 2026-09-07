<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SchoolTeacher extends Model
{
    protected $fillable = [
        'institution_id',
        'teacher_code',
        'name',
        'gender',
        'level',
        'phone',
        'qr_token',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SchoolTeacher $teacher) {
            if (empty($teacher->qr_token)) {
                $teacher->qr_token = (string) Str::uuid();
            }
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SchoolTeacherAttendance::class);
    }

    public function attendanceExcuses(): HasMany
    {
        return $this->hasMany(SchoolTeacherAttendanceExcuse::class);
    }

    public function levelLabel(): string
    {
        return match ($this->level) {
            'mi' => 'MI',
            'mts' => 'MTs',
            'ma' => 'MA',
            'mts_ma' => 'MTs & MA',
            default => '-',
        };
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            'putra' => 'Laki-laki',
            'putri' => 'Perempuan',
            default => '-',
        };
    }
}
