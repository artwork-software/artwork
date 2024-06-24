<?php

namespace Artwork\Modules\Shift\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Artwork\Core\Casts\TimeWithoutSeconds;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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
 */
class Shift extends Model
{
    use HasFactory;
    use HasChangesHistory;
    use SoftDeletes;

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
        'committing_user_id'
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
        'is_committed' => 'boolean',
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
    ];

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

    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'shift_user')
            ->using(ShiftUser::class)
            ->withPivot(['id', 'shift_qualification_id', 'shift_count'])
            ->without('calendar_settings');
    }

    public function freelancer(): BelongsToMany
    {
        return $this
            ->belongsToMany(Freelancer::class, 'shifts_freelancers')
            ->using(ShiftFreelancer::class)
            ->withPivot(['id', 'shift_qualification_id', 'shift_count']);
    }

    public function serviceProvider(): BelongsToMany
    {
        return $this
            ->belongsToMany(ServiceProvider::class, 'shifts_service_providers')
            ->using(ShiftServiceProvider::class)
            ->withPivot(['id', 'shift_qualification_id', 'shift_count']);
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
        $days_period = CarbonPeriod::create($this->start_date, $this->end_date);
        $days = [];

        foreach ($days_period as $day) {
            $days[] = $day->format('d.m.Y');
        }

        return $days;
    }

    public function shiftsQualifications(): HasMany
    {
        return $this->hasMany(ShiftsQualifications::class);
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
            ->orWhereBetween('event_end_day', [$eventStartDay, $eventEndDay]);
    }

    public function scopeStartAndEndOverlap(Builder $builder, string $start, string $end): Builder
    {
        return $builder
            ->whereBetween('start', [$start, $end])
            ->orWhereBetween('end', [$start, $end])
            ->orWhere(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->where('start', '>', $start)
                    ->where('end', '<', $end);
            })
            ->orWhere(function (Builder $builder) use ($start, $end): void {
                $builder
                    ->where('start', '<', $start)
                    ->where('end', '>', $end);
            });
    }

    public function scopeEventIdInArray(Builder $builder, array $eventIds): Builder
    {
        return $builder->whereIntegerInRaw('event_id', $eventIds);
    }

    public function scopeOrderedByStart(Builder $builder, string $direction = 'asc'): Builder
    {
        return $builder->orderBy('start', $direction);
    }

    public function getMaxUsersAttribute(): int
    {
        return $this->shiftsQualifications->sum('value');
    }
}
