<?php

namespace Artwork\Modules\Shift\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Casts\TimeWithoutSeconds;
use App\Models\Event;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\User;
use Artwork\Modules\Craft\Models\Craft;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $event_id
 * @property string $start
 * @property string $end
 * @property int $break_minutes
 * @property int $craft_id
 * @property int $number_employees
 * @property int $number_masters
 * @property string $description
 * @property bool $is_committed
 * @property string|null $shift_uuid
 * @property string|null $event_start_day
 * @property string|null $event_end_day
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \Artwork\Modules\Craft\Models\Craft $craft
 * @property-read \App\Models\Event $event
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Freelancer[] $freelancer
 * @property-read int|null $freelancer_count
 * @property-read int $currentCount
 * @property-read int $empty_master_count
 * @property-read int $empty_user_count
 * @property-read int $maxCount
 * @property-read float $master_count
 * @property-read int $user_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Support\Collection $history
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ServiceProvider[] $serviceProvider
 * @property-read int|null $service_provider_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $masters
 * @property-read int|null $masters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $employees
 * @property-read int|null $employees_count
 * @property-read array $allUsers
 * @property-read bool $infringement
 * @property-read string $break_formatted
 * @property-read \App\Models\User|null $committedBy
 * @property-read Collection<ShiftsQualifications> $shiftsQualifications
 */
class Shift extends Model
{
    use HasFactory;
    use HasChangesHistory;

    protected $fillable = [
        'event_id',
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
        'is_committed' => 'boolean'
    ];

    protected $with = ['craft', 'users', 'freelancer', 'serviceProvider', 'committedBy'];

    protected $appends = [
        'break_formatted',
        'infringement'
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
        $hours = intdiv($this->break_minutes, 60) . ':' . ($this->break_minutes % 60);
        return Carbon::parse($hours)->format('H:i');
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
}
