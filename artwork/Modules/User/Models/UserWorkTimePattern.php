<?php

namespace Artwork\Modules\User\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property Carbon $monday
 * @property Carbon $tuesday
 * @property Carbon $wednesday
 * @property Carbon $thursday
 * @property Carbon $friday
 * @property Carbon $saturday
 * @property Carbon $sunday
 */

class UserWorkTimePattern extends Model
{
    use HasFactory;
    use Searchable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $fillable = [
        'name',
        'description',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monday' => 'datetime:H:i',
        'tuesday' => 'datetime:H:i',
        'wednesday' => 'datetime:H:i',
        'thursday' => 'datetime:H:i',
        'friday' => 'datetime:H:i',
        'saturday' => 'datetime:H:i',
        'sunday' => 'datetime:H:i'
    ];

    protected $appends = [
        'full_work_time_in_hours'
    ];

    /**
     * The "booted" method of the model.
     */
    public function searchableAs(): string
    {
        return 'user_work_time_patterns_index';
    }

    /**
     * Get the indexable data array for the model.
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'monday' => $this->monday->format('H:i'),
            'tuesday' => $this->tuesday->format('H:i'),
            'wednesday' => $this->wednesday->format('H:i'),
            'thursday' => $this->thursday->format('H:i'),
            'friday' => $this->friday->format('H:i'),
            'saturday' => $this->saturday->format('H:i'),
            'sunday' => $this->sunday->format('H:i')
        ];
    }

    public function userWorkTime(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserWorkTime::class, 'work_time_pattern_id', 'id');
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
