<?php

namespace Artwork\Modules\Event\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventComment\Models\EventComment;
use Artwork\Modules\EventProperty\Models\EventProperty;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\SeriesEvents\Models\SeriesEvents;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\SubEvent\Models\SubEvent;
use Artwork\Modules\Timeline\Models\Timeline;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
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
 * @property Carbon $earliest_start_datetime
 * @property Carbon $latest_end_datetime
 * @property Carbon $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $accepted
 * @property string $option_string
 * @property Project|null $project
 * @property Room|null $room
 * @property EventType $event_type
 * @property User $creator
 * @property \Illuminate\Database\Eloquent\Collection<SubEvent> $subEvents
 * @property \Illuminate\Database\Eloquent\Collection<Shift> $shifts
 * @property \Illuminate\Database\Eloquent\Collection<Timeline> $timelines
 * @property \Illuminate\Database\Eloquent\Collection<EventComment> $comments
 * @property SeriesEvents|null $series
 * @property-read array<string> $days_of_event
 * @property-read array<string> $days_of_shifts
 */
class Event extends Model
{
    use HasChangesHistory;
    use HasFactory;
    use SoftDeletes;
    use Prunable;

    protected $with = [
        //'series',
        //'event_type',
        //'subEvents'
    ];

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
        'allDay',
        'latest_end_datetime',
        'earliest_start_datetime',
        'event_status_id'
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
        'allDay' => 'boolean',
        'earliest_start_datetime' => 'datetime',
        'latest_end_datetime' => 'datetime',
    ];

    protected $appends = [
        'days_of_event',
        'start_time_without_day',
        'end_time_without_day',
        'event_date_without_time',
        'formatted_dates',
        'dates_for_series_event',
        'times_without_dates',
        'start_hour',
        'event_length_in_hours',
        'hours_to_next_day',
        'minutes_form_start_hour_to_start',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (Event $event): void {
            /** @var EventService $eventService */
            $eventService = app()->get(EventService::class);
            $event->earliest_start_datetime = $eventService->getEarliestStartTime($event);
            $event->latest_end_datetime = $eventService->getLatestEndTime($event);
        });
    }

    public function comments(): HasMany
    {
        return $this->hasMany(EventComment::class)->orderBy('id', 'DESC');
    }

    public function timelines(): HasMany
    {
        return $this->hasMany(Timeline::class);
    }

    public function eventStatus(): BelongsTo
    {
        return $this->belongsTo(
            EventStatus::class,
            'event_status_id',
            'id',
            'event_statuses'
        );
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'event_id', 'id');
    }

    //@todo: fix phpcs error - refactor function name to eventType
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function event_type(): BelongsTo
    {
        return $this->belongsTo(
            EventType::class,
            'event_type_id',
            'id',
            'event_types'
        );
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(
            Room::class,
            'room_id',
            'id',
            'rooms'
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class,
            'project_id',
            'id',
            'projects'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'users'
        );
    }

    public function series(): HasOne
    {
        return $this->hasOne(SeriesEvents::class, 'id', 'series_id');
    }

    public function subEvents(): HasMany
    {
        return $this->hasMany(SubEvent::class)->orderBy('start_time', 'ASC');
    }

    public function eventProperties(): BelongsToMany
    {
        return $this->belongsToMany(EventProperty::class);
    }

    public function getStartTimeWithoutDayAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i');
    }

    public function getEndTimeWithoutDayAttribute(): string
    {
        return Carbon::parse($this->end_time)->format('H:i');
    }


    /**
     * @return array<string, string>
     */
    public function getFormattedDatesAttribute(): array
    {
        return [
            'start' => Carbon::parse($this->start_time)->translatedFormat('d.m.Y'),
            'end' => Carbon::parse($this->end_time)->translatedFormat('d.m.Y'),
            'start_without_time' => Carbon::parse($this->start_time)->translatedFormat('Y-m-d'),
            'start_with_time' => Carbon::parse($this->start_time)->translatedFormat('Y-m-d H:i'),
            'end_without_time' => Carbon::parse($this->end_time)->translatedFormat('Y-m-d'),
            'end_with_time' => Carbon::parse($this->end_time)->translatedFormat('Y-m-d H:i'),
            'startTime' => Carbon::parse($this->start_time)->translatedFormat('H:i'),
            'endTime' => Carbon::parse($this->end_time)->translatedFormat('H:i'),
            'start_without_year' => Carbon::parse($this->start_time)->translatedFormat('d.m'),
            'end_without_year' => Carbon::parse($this->end_time)->translatedFormat('d.m'),
            'startDateTime_without_year' => Carbon::parse($this->start_time)->translatedFormat('d.m H:i'),
            'endDateTime_without_year' => Carbon::parse($this->end_time)->translatedFormat('d.m H:i'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getEventDateWithoutTimeAttribute(): array
    {
        return [
            'start' => Carbon::parse($this->start_time)->format('d.m.Y'),
            'end' => Carbon::parse($this->end_time)->format('d.m.Y'),
            'start_clear' => Carbon::parse($this->start_time)->format('Y-m-d')
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getTimesWithoutDatesAttribute(): array
    {
        return [
            'start' => Carbon::parse($this->start_time)->format('H:i'),
            'end' => Carbon::parse($this->end_time)->format('H:i')
        ];
    }

    public function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
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

    /**
     * @param Shift|Collection|null $shifts
     * @return array<string>
     */
    public function getDaysOfShifts(Shift|Collection $shifts = null): array
    {
        if ($shifts instanceof Shift) {
            $shifts = collect([$shifts]);
        }
        $days = [];

        if (!$shifts) {
            $shifts = $this->shifts;
        }

        foreach ($shifts as $shift) {
            if ($shift->start_date === null || $shift->end_date === null) {
                continue;
            }
            $days_period = CarbonPeriod::create($shift->start_date, $shift->end_date);
            foreach ($days_period as $day) {
                $days[] = $day->format('d.m.Y');
            }
        }

        return $days;
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

    public function prunable(): Builder
    {
        return static::where('deleted_at', '<=', now()->subMonth())->withTrashed();
    }

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

    public function scopeStartAndEndTimeOverlap(Builder $builder, Carbon $start, Carbon $end): Builder
    {
        return $builder->where(
            function (Builder $query) use ($start, $end): void {
                // Events, die innerhalb des gegebenen Zeitraums starten und enden
                $query->whereBetween('start_time', [$start, $end])
                    ->whereBetween('end_time', [$start, $end]);
            }
        )->orWhere(
            function (Builder $query) use ($start, $end): void {
                // Events, die vor dem gegebenen Startdatum beginnen und nach dem gegebenen Enddatum enden
                $query->where('start_time', '<', $start)
                    ->where('end_time', '>', $end);
            }
        )->orWhere(
            function (Builder $query) use ($start, $end): void {
                // Events, die vor dem gegebenen Startdatum beginnen und innerhalb des gegebenen Zeitraums enden
                $query->where('start_time', '<', $start)
                    ->whereBetween('end_time', [$start, $end]);
            }
        )->orWhere(
            function (Builder $query) use ($start, $end): void {
                // Events, die innerhalb des gegebenen Zeitraums starten und nach dem gegebenen Enddatum enden
                $query->whereBetween('start_time', [$start, $end])
                    ->where('end_time', '>', $end);
            }
        );
    }

    public function scopeIsNotId(Builder $builder, int $eventId): Builder
    {
        return $builder->where('id', '!=', $eventId);
    }

    public function scopeOccursAt(Builder $builder, Carbon $carbon): Builder
    {
        return $builder
            ->whereDate('start_time', '<=', $carbon)
            ->whereDate('end_time', '>=', $carbon);
    }

    public function scopeOrderByStartTime(Builder $builder, string $direction = 'ASC'): Builder
    {
        return $builder->orderBy('start_time', $direction);
    }

    /**
     * @return array<string, string>
     */
    public function getDatesForSeriesEventAttribute(): array
    {
        if (!$this->is_series) {
            return [];
        }

        return [
            'start' => Carbon::parse($this->start_time)->format('Y-m-d'),
            'end' => Carbon::parse($this->series->end_date)->format('Y-m-d'),
            'formatted_start' => Carbon::parse($this->start_time)->translatedFormat('d. M Y'),
            'formatted_end' => Carbon::parse($this->series->end_date)->translatedFormat('d. M Y')
        ];
    }

    public function scopeGetOrderBySubQueryBuilder(
        Builder $builder,
        string $columnToOrderBy,
        string $direction
    ): Builder {
        return $builder
            ->select($columnToOrderBy)
            ->whereRaw('`projects`.`id` = `events`.`project_id`')
            ->orderBy($columnToOrderBy, $direction)
            ->take(1);
    }

    public function getStartHourAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H');
    }

    public function getEventLengthInHoursAttribute(): float
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        if ($start->isSameDay($end)) {
            // Differenz in Stunden und Minuten berechnen
            $diffInMinutes = $end->diffInMinutes($start);
            return round($diffInMinutes / 60, 2); // In Stunden umrechnen
        } else {
            // Wenn nicht am selben Tag: Bis zum Tagesende des Starttags berechnen
            $diffInMinutes = $start->endOfDay()->diffInMinutes($start);
            return round($diffInMinutes / 60, 2); // In Stunden umrechnen
        }
    }


    public function getHoursToNextDayAttribute(): int
    {
        return Carbon::parse($this->end_time)->diffInHours(Carbon::parse($this->start_time)->endOfDay());
    }

    public function getMinutesFormStartHourToStartAttribute(): int
    {
        return Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->start_time)->startOfHour());
    }
}
