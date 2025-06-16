<?php

namespace Artwork\Modules\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWorkTime extends Model
{
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
        'sunday'
    ];

    protected $casts = [
        'monday' => 'datetime:H:i',
        'tuesday' => 'datetime:H:i',
        'wednesday' => 'datetime:H:i',
        'thursday' => 'datetime:H:i',
        'friday' => 'datetime:H:i',
        'saturday' => 'datetime:H:i',
        'sunday' => 'datetime:H:i'
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

}
