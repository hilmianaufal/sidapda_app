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
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
