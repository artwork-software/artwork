<?php

namespace Artwork\Modules\Shift\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $event_id
 * @property string $start_date
 * @property string $end_date
 * @property string $start
 * @property string $end
 * @property int $break_minutes
 * @property int $craft_id
 * @property string $description
 * @property bool $is_committed
 * @property string|null $shift_uuid
 * @property string|null $event_start_day
 * @property string|null $event_end_day
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int $committing_user_id
 * @property-read Craft $craft
 * @property-read Event $event
 * @property-read Collection<Freelancer> $freelancer
 * @property-read Collection<User> $users
 * @property-read Collection<ServiceProvider> $serviceProvider
 * @property-read \Illuminate\Support\Collection $history
 * @property-read array $allUsers
 * @property-read bool $infringement
 * @property-read string $break_formatted
 * @property-read User|null $committedBy
 * @property-read Collection<ShiftsQualifications> $shiftsQualifications
 * @property-read array $formatted_dates
 * @property-read array $days_of_shift
 * @property-read int $max_users
 * @method static Builder isCommitted()
 * @property-read Collection<GlobalQualification> $globalQualifications
 * @property-read Project $project
 */
class Shift extends Model
{
    use HasFactory;
    use HasChangesHistory;
    use SoftDeletes;
    use LogsActivity;


    protected $fillable = [
        'event_id',
        'start_date',
        'end_date',
        'start',
        'end',
        'break_minutes',
        'craft_id',
        'description',
        'is_committed',
        'shift_uuid',
        'event_start_day',
        'event_end_day',
        'committing_user_id',
        'room_id',
        'project_id',
        'shift_group_id',
        'in_workflow',
        'current_request_id',
        'workflow_rejection_reason'
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
        'is_committed' => 'boolean',
        'in_workflow'   => 'boolean',
        'start_date' => 'datetime:d. M Y',
        'end_date' => 'datetime:d. M Y',
    ];

    protected $with = [
        'craft',
        'users',
        'freelancer',
        'serviceProvider',
        'committedBy'
    ];

    protected $appends = [
        'break_formatted',
        'infringement',
        'formatted_dates',
        'max_users',
        'days_of_shift'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('shift')
            ->logOnly([
                'start_date',
                'end_date',
                'start',
                'end',
                'break_minutes',
                'craft.name',
                'description',
                'room.name',
                'project.name',
                'shiftGroup.name',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge([
            'context'       => $this->is_committed ? 'post_commit' : ($this->in_workflow ? 'in_workflow' : 'normal'),
            'shift_id'      => $this->id,
            'craft_id'      => $this->craft_id,
            'project_id'    => $this->project_id,
            'current_request_id' => $this->current_request_id,
        ]);
    }

    public function committedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'committing_user_id',
            'id',
            'users'
        )->withoutEagerLoad(['calender_settings']);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class,
            'event_id',
            'id',
            'events'
        )->without(['series']);
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(
            Craft::class,
            'craft_id',
            'id',
            'crafts'
        )->without(['users']);
    }

    public function globalQualifications(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalQualification::class,
            'shift_global_qualifications',
            'shift_id',
            'global_qualification_id'
        )->withPivot('quantity');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id', 'rooms');
    }

    /** project */
    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class,
            'project_id',
            'id',
            'projects'
        )->without(['components', 'users']);
    }

    public function users(): MorphToMany
    {
        return $this
            ->morphedByMany(User::class, 'employable', 'shift_workers', 'shift_id', 'employable_id')
            ->using(ShiftWorker::class)
            ->where('shift_workers.employable_type', User::class)
            ->withPivot(['id', 'shift_qualification_id', 'shift_count', 'craft_abbreviation', 'short_description', 'start_date', 'end_date', 'start_time', 'end_time'])
            ->without('calendar_settings');
    }

    public function freelancer(): MorphToMany
    {
        return $this
            ->morphedByMany(Freelancer::class, 'employable', 'shift_workers', 'shift_id', 'employable_id')
            ->using(ShiftWorker::class)
            ->where('shift_workers.employable_type', Freelancer::class)
            ->withPivot(['id', 'shift_qualification_id', 'shift_count', 'craft_abbreviation', 'short_description', 'start_date', 'end_date', 'start_time', 'end_time']);
    }

    public function serviceProvider(): MorphToMany
    {
        return $this
            ->morphedByMany(ServiceProvider::class, 'employable', 'shift_workers', 'shift_id', 'employable_id')
            ->using(ShiftWorker::class)
            ->where('shift_workers.employable_type', ServiceProvider::class)
            ->withPivot(['id', 'shift_qualification_id', 'shift_count', 'craft_abbreviation', 'short_description', 'start_date', 'end_date', 'start_time', 'end_time']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getFormattedDatesAttribute(): array
    {
        return [
            'start' => Carbon::parse($this->start_date)->format('d.m.Y'),
            'end' => Carbon::parse($this->end_date)->format('d.m.Y'),
            'frontend_start' => Carbon::parse($this->start_date)->format('Y-m-d'),
            'frontend_end' => Carbon::parse($this->end_date)->format('Y-m-d')
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDaysOfShiftAttribute(): array
    {
        if (!$this->start_date || !$this->end_date) {
            return [];
        }

        return collect(CarbonPeriod::create($this->start_date, $this->end_date))
            ->map(fn($day) => $day->format('d.m.Y'))
            ->all();
    }


    public function shiftsQualifications(): HasMany
    {
        return $this->hasMany(ShiftsQualifications::class, 'shift_id', 'id');
    }

    public function getHistoryAttribute(): Collection
    {
        return $this->historyChanges()->sortByDesc('created_at');
    }

    public function getBreakFormattedAttribute(): string
    {
        $totalMinutes = $this->break_minutes; // 150000 oder ein anderer Wert
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        // Führende Nullen für Stunden und Minuten hinzufügen, falls nötig
        $formattedHours = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $formattedMinutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);

        return $formattedHours . ':' . $formattedMinutes;
    }

    public function getInfringementAttribute(): bool
    {
        $start = Carbon::parse($this->start);
        $end = Carbon::parse($this->end);
        $diff = $start->diffInRealMinutes($end);
        $break = $this->break_minutes;

        if (($diff > 360 && $diff < 540 && $break < 30) || ($diff > 540 && $break < 45)) {
            return true;
        }
        return false;
    }

    public function scopeIsCommitted(Builder $query): Builder
    {
        return $query->where('is_committed', true);
    }

    public function scopeAllByUuid(Builder $builder, string $shiftUuid): Builder
    {
        return $builder->where('shift_uuid', $shiftUuid);
    }

    public function scopeEventStartDayAndEventEndDayBetween(
        Builder $builder,
        Carbon $eventStartDay,
        Carbon $eventEndDay
    ): Builder {
        return $builder
            ->whereBetween('event_start_day', [$eventStartDay, $eventEndDay])
            ->orWhereBetween('event_end_day', [$eventStartDay, $eventEndDay])
            ->orWhereBetween('shifts.start_date', [$eventStartDay, $eventEndDay])
            ->orWhereBetween('shifts.end_date', [$eventStartDay, $eventEndDay]);
    }

    public function scopeStartAndEndOverlap(Builder $builder, string $start, string $end): Builder
    {
        return $builder
            ->whereBetween('shifts.start', [$start, $end])
            ->orWhereBetween('shifts.end', [$start, $end])
            ->orWhere(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->where('shifts.start', '>', $start)
                    ->where('shifts.end', '<', $end);
            })
            ->orWhere(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->where('shifts.start', '<', $start)
                    ->where('shifts.end', '>', $end);
            });
    }

    public function scopeStartAndEndDateOverlap(Builder $builder, string $start, string $end): Builder
    {
        return $builder
            ->whereBetween('shifts.start_date', [$start, $end])
            ->orWhereBetween('shifts.end_date', [$start, $end])
            ->orWhere(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->where('shifts.start_date', '>', $start)
                    ->where('shifts.end_date', '<', $end);
            })
            ->orWhere(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->where('shifts.start_date', '<', $start)
                    ->where('shifts.end_date', '>', $end);
            });
    }


    public function scopeEventIdInArray(Builder $builder, ?array $eventIds = []): Builder
    {
        return $builder->whereIntegerInRaw('event_id', $eventIds);
    }

    public function scopeOrderedByStart(Builder $builder, string $direction = 'asc'): Builder
    {
        return $builder->orderBy('shifts.start', $direction);
    }

    public function getMaxUsersAttribute(): int
    {
        return $this->shiftsQualifications->sum('value');
    }

    // shift group relation
    public function shiftGroup(): BelongsTo
    {
        return $this->belongsTo(
            ShiftGroup::class,
            'shift_group_id',
            'id',
            'shift_groups'
        );
    }

    public function shiftRuleViolations(): HasMany
    {
        return $this->hasMany(ShiftRuleViolation::class);
    }

    public function shiftPlanRequestChanges(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(
            ShiftPlanRequestChange::class,
            'subject',
            'subject_type',
            'subject_id'
        );
    }

    public function committedShiftChanges(): HasMany
    {
        return $this->hasMany(CommittedShiftChange::class, 'shift_id', 'id');
    }

    public function currentRequest()
    {
        return $this->belongsTo(ShiftPlanRequest::class, 'current_request_id', 'id', 'shift_plan_requests');
    }

    /**
     * Historical requests this shift was part of
     */
    public function requestHistories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \Artwork\Modules\Shift\Models\ShiftPlanRequest::class,
            'shift_plan_request_shifts',
            'shift_id',
            'shift_plan_request_id'
        )->withPivot(['snapshot'])->withTimestamps();
    }
}
