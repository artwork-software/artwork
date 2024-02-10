<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Builders\EventBuilder;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Timeline\Models\Timeline;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $eventName
 * @property string $description
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property bool $occupancy_option
 * @property bool $audience
 * @property bool $is_loud
 * @property bool $allDay
 * @property int $event_type_id
 * @property int $room_id
 * @property int $declined_room_id
 * @property int $user_id
 * @property int $project_id
 * @property bool $is_series
 * @property int $series_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $accepted
 * @property string $option_string
 * @property \Illuminate\Database\Eloquent\Collection<Shift> $shifts
 * @property \Illuminate\Database\Eloquent\Collection<Timeline> $timeline
 */
class Event extends Model
{
    use HasChangesHistory;
    use HasFactory;
    use SoftDeletes;

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
        'declined_room_id',
        'allDay'
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
        'accepted' => 'boolean',
        'allDay' => 'boolean'
    ];

    protected $appends = [
        'days_of_event',
        'start_time_without_day',
        'end_time_without_day'
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(EventComments::class)->orderBy('id', 'DESC');
    }

    /**
     * @return array<string, string>
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

    public function timeline(): HasMany
    {
        return $this->hasMany(Timeline::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function getStartTimeWithoutDayAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    public function getEndTimeWithoutDayAttribute(): string
    {
        return Carbon::parse($this->end_time)->format('H:i');
    }

    public function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    //@todo: fix phpcs error - refactor function name to eventType
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function event_type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id', 'id', 'event_types');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id', 'rooms');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'projects');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    public function roomAdministrators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'room_user', 'user_id', 'room_id', 'room_id', 'id');
    }

    public function sameRoomEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'room_id', 'room_id');
    }

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

    public function series(): HasOne
    {
        return $this->hasOne(SeriesEvents::class, 'id', 'series_id');
    }

    public function subEvents(): HasMany
    {
        return $this->hasMany(SubEvents::class)->orderBy('start_time', 'ASC');
    }

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

        return $this->start_time->isBetween($event->start_time, $event->end_time) ||
            $this->end_time->isBetween($event->start_time, $event->end_time);
    }

    // scopes

    public function scopeHasNoRoom(Builder $builder): Builder
    {
        return $builder->whereNull('room_id');
    }

    public function scopeByProjectId(Builder $builder, int $projectId): Builder
    {
        return $builder->where('project_id', $projectId);
    }

    public function scopeByEventTypeId(Builder $builder, int $eventTypeId): Builder
    {
        return $builder->where('event_type_id', $eventTypeId);
    }
}
