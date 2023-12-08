<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Builders\EventBuilder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Event extends Model
{
    use HasChangesHistory;
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $with = ['series', 'event_type'];

    /**
     * @var string[]
     */
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
        'declined_room_id',
        'allDay'
    ];

    /**
     * @var string[]
     */
    protected $guarded = [
        'id',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'is_loud' => 'boolean',
        'audience' => 'boolean',
        'occupancy_option' => 'boolean',
        'start_time' => 'datetime:d. M Y H:i',
        'end_time' => 'datetime:d. M Y H:i',
        'is_series' => 'boolean',
        'accepted' => 'boolean',
        'allDay' => 'boolean'
    ];

    /**
     * @var string[]
     */
    protected $appends = [
        'days_of_event', 'start_time_without_day', 'end_time_without_day'
    ];

    /**
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(EventComments::class)->orderBy('id', 'DESC');
    }

    /**
     * @return array
     */
    public function getDaysOfEventAttribute(): array
    {
        $days_period = CarbonPeriod::create($this->start_time, $this->end_time);
        $days = [];

        foreach ($days_period as $day) {
            $days[] = $day->format('d.m.Y');
        }

        return $days;
    }

    /**
     * @return HasMany
     */
    public function timeline(): HasMany
    {
        return $this->hasMany(TimeLine::class);
    }

    /**
     * @return HasMany
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * @return string
     */
    public function getStartTimeWithoutDayAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    /**
     * @return string
     */
    public function getEndTimeWithoutDayAttribute(): string
    {
        return Carbon::parse($this->end_time)->format('H:i');
    }

    /**
     * @param DateTimeInterface $date
     * @return string
     */
    public function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * @return BelongsTo
     */
    public function event_type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    /**
     * @return BelongsTo
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * @return BelongsTo
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function roomAdministrators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'user_id', 'room_id', 'room_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function sameRoomEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'room_id', 'room_id');
    }

    /**
     * @return BelongsToMany
     */
    public function adjoiningEvents(): BelongsToMany
    {
        return $this->belongsToMany(
            Event::class,
            'adjoining_room_main_room',
            'main_room_id',
            'adjoining_room_id',
            'room_id',
            'room_id'
        );
    }

    /**
     * @return HasOne
     */
    public function series(): HasOne
    {
        return $this->hasOne(SeriesEvents::class, 'id', 'series_id');
    }

    /**
     * @return HasMany
     */
    public function subEvents(): HasMany
    {
        return $this->hasMany(SubEvents::class)->orderBy('start_time', 'ASC');
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     * @return EventBuilder
     */
    public function newEloquentBuilder($query): EventBuilder
    {
        return new EventBuilder($query);
    }

    /**
     * @param Carbon $date
     * @return int
     */
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

    /**
     * @param Carbon $dateTime
     * @param bool $precisionDateTime
     * @return bool
     */
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

    /**
     * @param Collection $events
     * @return bool
     */
    public function conflictsWithAny(Collection $events): bool
    {
        return $events->unique()
            ->contains(fn (Event $event) => $this->conflictsWith($event));
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function conflictsWith(Event $event): bool
    {
        if ($event->id === $this->id) {
            return false;
        }

        return $this->start_time->isBetween($event->start_time, $event->end_time)
            || $this->end_time->isBetween($event->start_time, $event->end_time);
    }
}
