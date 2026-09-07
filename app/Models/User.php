<?php

namespace App\Models;
use Spatie\Permission\Traits\HasRoles;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
       'name','email','phone','password','is_active','last_login_at','notes','avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function avatarUrl(): string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . $this->avatar);
        }

        return asset('images/default.jpg');
    }

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Query lembaga aktif yang boleh diakses akun ini.
     */
    public function accessibleInstitutions(): Builder
    {
        $query = Institution::query()
            ->where('institutions.is_active', true)
            ->orderBy('institutions.sort_order');

        if (! $this->hasRole('admin')) {
            $query->whereHas('users', fn (Builder $users) => $users
                ->where('users.id', $this->id)
                ->where('institution_user.is_active', true));
        }

        return $query;
    }

    public function accessibleInstitutionCodes(): array
    {
        return $this->accessibleInstitutions()
            ->pluck('institutions.code')
            ->all();
    }

    public function canAccessInstitution(Institution|int|string $institution): bool
    {
        if ($institution instanceof Institution) {
            if (! $institution->is_active) {
                return false;
            }

            return $this->hasRole('admin') || $this->institutions()
                ->where('institutions.id', $institution->id)
                ->where('institutions.is_active', true)
                ->wherePivot('is_active', true)
                ->exists();
        }

        $column = is_int($institution) || ctype_digit((string) $institution)
            ? 'institutions.id'
            : 'institutions.code';

        return $this->accessibleInstitutions()
            ->where($column, $institution)
            ->exists();
    }

    public function canManageInstitutionSettings(Institution $institution): bool
    {
        return $this->canAccessInstitution($institution)
            && ($this->hasRole('admin') || $this->hasRole('petugas') || $this->can('manage_users'));
    }

    /**
     * Batasi query siswa ke lembaga yang ditugaskan kepada akun.
     * Pondok memakai status mukim; lembaga sekolah/MADAD memakai enrollment.
     */
    public function scopeAccessibleStudents(Builder $query, ?string $academicYear = null): Builder
    {
        if ($this->hasRole('admin')) {
            return $query;
        }

        $academicYear ??= Student::academicYearForDate();
        $institutions = $this->accessibleInstitutions()->get(['institutions.id', 'institutions.code']);
        $institutionIds = $institutions->pluck('id')->all();
        $hasPondok = $institutions->contains('code', 'ponpes');

        if ($institutionIds === [] && ! $hasPondok) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $access) use ($academicYear, $institutionIds, $hasPondok) {
            if ($hasPondok) {
                $access->where('residency_status', 'mukim');
            }

            if ($institutionIds !== []) {
                $method = $hasPondok ? 'orWhereHas' : 'whereHas';
                $access->{$method}('enrollments', fn (Builder $enrollment) => $enrollment
                    ->whereIn('institution_id', $institutionIds)
                    ->where('academic_year', $academicYear)
                    ->where('is_active', true));
            }
        });
    }

    public function canAccessStudent(Student $student, ?string $academicYear = null): bool
    {
        return $this->scopeAccessibleStudents(
            Student::query()->whereKey($student->getKey()),
            $academicYear
        )->exists();
    }

}
