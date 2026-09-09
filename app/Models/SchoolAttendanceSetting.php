<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAttendanceSetting extends Model
{
    protected $fillable = [
        'institution_id',
        'check_in_open',
        'check_in_time',
        'check_in_deadline',
        'check_out_open',
        'check_out_time',
        'check_out_deadline',
        'is_active',
        'teacher_geofence_enabled',
        'teacher_geofence_latitude',
        'teacher_geofence_longitude',
        'teacher_geofence_radius_meters',
        'teacher_geofence_max_accuracy_meters',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'teacher_geofence_enabled' => 'boolean',
            'teacher_geofence_latitude' => 'float',
            'teacher_geofence_longitude' => 'float',
            'teacher_geofence_radius_meters' => 'integer',
            'teacher_geofence_max_accuracy_meters' => 'integer',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
