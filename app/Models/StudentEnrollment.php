<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentEnrollment extends Model
{
    public const MADAD_LEVELS = ['Ula', 'Wustha', 'Ulya'];

    protected $fillable = [
        'institution_id',
        'student_id',
        'academic_year',
        'class_name',
        'level',
        'is_active',
        'enrolled_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'enrolled_at' => 'date',
            'left_at' => 'date',
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

    public function promotion(): HasOne
    {
        return $this->hasOne(StudentPromotion::class, 'source_enrollment_id');
    }

    public static function madadLevels(): array
    {
        return self::MADAD_LEVELS;
    }

    public static function normalizeMadadLevel(mixed $value): ?string
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'ula' => 'Ula',
            'wustha', 'wustho' => 'Wustha',
            'ulya', 'ulya\'' => 'Ulya',
            default => null,
        };
    }
}
