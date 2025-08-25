<?php

namespace Artwork\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWorkTime extends Model
{
    protected $table = 'user_work_times';

    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_time_pattern_id',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday', 'valid_from', 'valid_until', 'is_active'
    ];

    protected $casts = [
        'monday' => 'datetime:H:i',
        'tuesday' => 'datetime:H:i',
        'wednesday' => 'datetime:H:i',
        'thursday' => 'datetime:H:i',
        'friday' => 'datetime:H:i',
        'saturday' => 'datetime:H:i',
        'sunday' => 'datetime:H:i',
        'valid_from' => 'date:Y-m-d',
        'valid_until' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_work_time_in_hours'
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user_work_times');
    }

    public function workTimePattern(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(
            UserWorkTimePattern::class,
            'work_time_pattern_id',
            'id',
            'user_work_time_patterns'
        );
    }

    public function getFullWorkTimeInHoursAttribute(): float
    {
        $totalMinutes = 0;

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
            $time = $this->{$day};
            if ($time) {
                $totalMinutes += $time->hour * 60 + $time->minute;
            }
        }

        return round($totalMinutes / 60, 2);
    }
}
