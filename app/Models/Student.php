<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Student extends Model
{
    protected $fillable = [
        'nis',
        'name',
        'kelas',
        'kamar',
        'gender',
        'residency_status',
        'is_active',
        'qr_token',
        'photo',
        'parent_phone',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            if (empty($student->qr_token)) {
                $student->qr_token = (string) Str::uuid();
            }
        });
    }

    public function photoUrl(): string
    {
        if ($this->photo && file_exists(public_path($this->photo))) {
            return asset($this->photo);
        }

        return asset('images/default.jpg');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class, 'student_enrollments')
            ->withPivot([
                'academic_year',
                'class_name',
                'level',
                'is_active',
                'enrolled_at',
                'left_at',
            ])
            ->withTimestamps();
    }

    public function scopeWithInstitutionClass(
        Builder $query,
        string $institutionCode,
        string $academicYear
    ): Builder {
        $studentId = $query->qualifyColumn('id');

        if ($query->getQuery()->columns === null) {
            $query->select($query->qualifyColumn('*'));
        }

        return $query->addSelect([
            'institution_class' => StudentEnrollment::query()
                ->select('student_enrollments.class_name')
                ->join('institutions', 'institutions.id', '=', 'student_enrollments.institution_id')
                ->whereColumn('student_enrollments.student_id', $studentId)
                ->where('student_enrollments.academic_year', $academicYear)
                ->where('student_enrollments.is_active', true)
                ->where('institutions.code', $institutionCode)
                ->where('institutions.is_active', true)
                ->latest('student_enrollments.id')
                ->limit(1),
            'institution_level' => StudentEnrollment::query()
                ->select('student_enrollments.level')
                ->join('institutions', 'institutions.id', '=', 'student_enrollments.institution_id')
                ->whereColumn('student_enrollments.student_id', $studentId)
                ->where('student_enrollments.academic_year', $academicYear)
                ->where('student_enrollments.is_active', true)
                ->where('institutions.code', $institutionCode)
                ->where('institutions.is_active', true)
                ->latest('student_enrollments.id')
                ->limit(1),
        ]);
    }

    public function scopeWhereInstitutionClass(
        Builder $query,
        string $institutionCode,
        string $academicYear,
        string $className
    ): Builder {
        return $query->whereHas('enrollments', function (Builder $enrollment) use (
            $institutionCode,
            $academicYear,
            $className
        ) {
            $enrollment
                ->where('academic_year', $academicYear)
                ->where('class_name', $className)
                ->where('is_active', true)
                ->whereHas('institution', fn (Builder $institution) => $institution
                    ->where('code', $institutionCode)
                    ->where('is_active', true));
        });
    }

    public function scopeWhereInstitutionLevel(
        Builder $query,
        string $institutionCode,
        string $academicYear,
        string $level
    ): Builder {
        return $query->whereHas('enrollments', function (Builder $enrollment) use (
            $institutionCode,
            $academicYear,
            $level
        ) {
            $enrollment
                ->where('academic_year', $academicYear)
                ->where('level', $level)
                ->where('is_active', true)
                ->whereHas('institution', fn (Builder $institution) => $institution
                    ->where('code', $institutionCode)
                    ->where('is_active', true));
        });
    }

    public function boardingLeaves(): HasMany
    {
        return $this->hasMany(BoardingLeave::class);
    }

    public function scopeBoardingResidents(Builder $query): Builder
    {
        return $query
            ->where($query->qualifyColumn('is_active'), true)
            ->where($query->qualifyColumn('residency_status'), 'mukim');
    }

    public function scopeNotAwayFromBoarding(
        Builder $query,
        string|DateTimeInterface|null $at = null
    ): Builder {
        $reference = self::boardingReferenceAt($at);

        return $query->whereDoesntHave('boardingLeaves', function (Builder $leave) use ($reference) {
            $leave->where('departed_at', '<=', $reference)
                ->where(function (Builder $period) use ($reference) {
                    $period->whereNull('returned_at')
                        ->orWhere('returned_at', '>', $reference);
                });
        });
    }

    public function scopeObligatedForPrayer(
        Builder $query,
        string|DateTimeInterface|null $at = null
    ): Builder
    {
        return $query
            ->boardingResidents()
            ->notAwayFromBoarding($at);
    }

    public function scopeEligibleForActivity(
        Builder $query,
        string $category,
        ?string $academicYear = null
    ): Builder {
        if ($category !== 'diniyah') {
            return $query->boardingResidents();
        }

        $academicYear ??= self::academicYearForDate();

        return $query
            ->where($query->qualifyColumn('is_active'), true)
            ->whereHas('enrollments', function (Builder $enrollment) use ($academicYear) {
                $enrollment
                    ->where('academic_year', $academicYear)
                    ->where('is_active', true)
                    ->whereHas('institution', fn (Builder $institution) => $institution
                        ->where('code', 'madad')
                        ->where('is_active', true));
            });
    }

    public function scopeObligatedForActivity(
        Builder $query,
        string $category,
        ?string $academicYear = null,
        string|DateTimeInterface|null $at = null
    ): Builder {
        return $query
            ->eligibleForActivity($category, $academicYear)
            ->notAwayFromBoarding($at);
    }

    public function isAwayFromBoarding(string|DateTimeInterface|null $at = null): bool
    {
        $reference = self::boardingReferenceAt($at);

        if ($this->relationLoaded('boardingLeaves')) {
            return $this->boardingLeaves->contains(function (BoardingLeave $leave) use ($reference) {
                return $leave->departed_at?->lte($reference)
                    && ($leave->returned_at === null || $leave->returned_at->gt($reference));
            });
        }

        return $this->boardingLeaves()
            ->where('departed_at', '<=', $reference)
            ->where(function (Builder $period) use ($reference) {
                $period->whereNull('returned_at')
                    ->orWhere('returned_at', '>', $reference);
            })
            ->exists();
    }

    public function isObligatedForPrayer(string|DateTimeInterface|null $at = null): bool
    {
        return $this->is_active
            && $this->residency_status === 'mukim'
            && ! $this->isAwayFromBoarding($at);
    }

    public function isEligibleForActivity(string $category, ?string $academicYear = null): bool
    {
        if ($category !== 'diniyah') {
            return $this->is_active && $this->residency_status === 'mukim';
        }

        if (! $this->is_active) {
            return false;
        }

        $academicYear ??= self::academicYearForDate();

        return $this->enrollments()
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->whereHas('institution', fn (Builder $institution) => $institution
                ->where('code', 'madad')
                ->where('is_active', true))
            ->exists();
    }

    public function isObligatedForActivity(
        string $category,
        ?string $academicYear = null,
        string|DateTimeInterface|null $at = null
    ): bool {
        return $this->isEligibleForActivity($category, $academicYear)
            && ! $this->isAwayFromBoarding($at);
    }

    public static function boardingReferenceAt(string|DateTimeInterface|null $at = null): Carbon
    {
        if ($at instanceof DateTimeInterface) {
            return Carbon::instance($at)->copy();
        }

        if (is_string($at) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $at)) {
            return Carbon::parse($at)->endOfDay();
        }

        return Carbon::parse($at ?? now());
    }

    public static function academicYearForDate(string|DateTimeInterface|null $date = null): string
    {
        $date = $date instanceof DateTimeInterface
            ? Carbon::instance($date)
            : Carbon::parse($date ?? now());

        return $date->month >= 7
            ? $date->year.'/'.($date->year + 1)
            : ($date->year - 1).'/'.$date->year;
    }
}
