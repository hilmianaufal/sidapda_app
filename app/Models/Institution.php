<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $hidden = [
        'fonnte_token',
    ];

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'type',
        'description',
        'address',
        'phone',
        'email',
        'website',
        'leader_name',
        'color',
        'icon',
        'logo_path',
        'sort_order',
        'is_active',
        'fonnte_enabled',
        'fonnte_token',
        'fonnte_notify_attendance',
        'fonnte_notify_excuse',
        'fonnte_notify_boarding',
        'fonnte_message_footer',
        'fonnte_device_number',
        'fonnte_device_status',
        'fonnte_last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'fonnte_enabled' => 'boolean',
            'fonnte_token' => 'encrypted',
            'fonnte_notify_attendance' => 'boolean',
            'fonnte_notify_excuse' => 'boolean',
            'fonnte_notify_boarding' => 'boolean',
            'fonnte_last_tested_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_enrollments')
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(InstitutionWhatsappLog::class);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return asset('uploads/'.$this->logo_path);
    }
}
