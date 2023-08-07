<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Builders\EventBuilder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 *
 * @property int $id
 * @property ?string $name
 * @property ?string $eventName
 * @property ?string $description
 * @property ?string $option_string
 * @property ?Carbon $start_time
 * @property ?Carbon $end_time
 * @property ?bool $occupancy_option
 * @property ?bool $audience
 * @property ?bool $is_loud
 * @property ?int $event_type_id
 * @property ?int $room_id
 * @property ?int $declined_room_id
 * @property int $user_id
 * @property ?int $project_id
 * @property int $series_id
 * @property int $is_series
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * @property EventType $event_type
 * @property Room $room
 * @property Project $project
 * @property User $creator
 * @property \Illuminate\Database\Eloquent\Collection<Event> $sameRoomEvents
 * @property \Illuminate\Database\Eloquent\Collection<Event> $adjoiningEvents
 */
class Event extends Model
{
    use HasChangesHistory;
    use HasFactory, SoftDeletes;

    protected $with = ['series', 'event_type'];

    protected $fillable = [
        'name',
        'eventName',
        'description',
        'start_time',
        'end_time',
        'occupancy_option',
        'audience',
        'is_loud',
        'event_type_id',
        'room_id',
        'user_id',
        'project_id',
        'series_id',
        'is_series',
        'accepted',
        'option_string',
        'declined_room_id'
    ];


    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'is_loud' => 'boolean',
        'audience' => 'boolean',
        'occupancy_option' => 'boolean',
        'start_time' => 'datetime:d. M Y H:i',
        'end_time' => 'datetime:d. M Y H:i',
        'is_series' => 'boolean',
        'accepted' => 'boolean'
    ];

    protected $appends = [
        'days_of_event', 'start_time_without_day', 'end_time_without_day'
    ];

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EventComments::class)->orderBy('id', 'DESC');
    }

    public function getDaysOfEventAttribute(): array
    {
        $days_period = CarbonPeriod::create($this->start_time, $this->end_time);
        $days = [];

        foreach ($days_period as $day) {
            $days[] = $day->format('d.m.Y');
        }

        return $days;
    }

    public function timeline(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TimeLine::class);
    }

    public function shifts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function getStartTimeWithoutDayAttribute()
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    public function getEndTimeWithoutDayAttribute()
    {
        return Carbon::parse($this->end_time)->format('H:i');
    }

    public function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function event_type()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function roomAdministrators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'user_id', 'room_id', 'room_id', 'id');
    }

    public function sameRoomEvents()
    {
        return $this->hasMany(Event::class, 'room_id', 'room_id');
    }

    public function adjoiningEvents()
    {
        return $this->belongsToMany(Event::class,
            'adjoining_room_main_room',
            'main_room_id',
            'adjoining_room_id',
            'room_id',
            'room_id'
        );
    }

    public function series()
    {
        return $this->hasOne(SeriesEvents::class, 'id', 'series_id');
    }

    public function subEvents()
    {
        return $this->hasMany(SubEvents::class)->orderBy('start_time', 'ASC');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function query(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::query();
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\EventBuilder
     */
    public function newEloquentBuilder($query): EventBuilder
    {
        return new EventBuilder($query);
    }

    public function getMinutesFromDayStart(Carbon $date): int
    {
        $startOfDay = $date->startOfDay()->subHours(2);

        if (Carbon::parse($this->start_time)->isBefore($startOfDay)) {
            return 1;
        }

        if ($startOfDay->diffInMinutes(Carbon::parse($this->start_time)) < 1440) {
            return $startOfDay->diffInMinutes(Carbon::parse($this->start_time));
        }

        return 1;
    }

    public function occursAtTime(Carbon $dateTime, bool $precisionDateTime = true): bool
    {
        // occurs on same day
        if (! $precisionDateTime) {
            return collect(CarbonPeriod::create($this->start_time, $this->end_time))
                ->contains(fn (Carbon $day) => $day->isSameDay($dateTime));
        }

        // occurs at same second
        return $this->start_time->lessThanOrEqualTo($dateTime) && $this->end_time->greaterThanOrEqualTo($dateTime);
    }

    public function conflictsWithAny(Collection $events): bool
    {
        return $events->unique()
            ->contains(fn (Event $event) => $this->conflictsWith($event));
    }

    public function conflictsWith(Event $event): bool
    {
        if ($event->id === $this->id) {
            return false;
        }

        return $this->start_time->isBetween($event->start_time, $event->end_time)
            || $this->end_time->isBetween($event->start_time, $event->end_time);
    }


}
