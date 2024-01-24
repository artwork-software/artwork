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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ServiceProvider[] $service_provider
 * @property-read int|null $service_provider_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $masters
 * @property-read int|null $masters_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $employees
 * @property-read int|null $employees_count
 * @property-read array $allUsers
 * @property-read bool $infringement
 * @property-read string $break_formatted
 * @property-read \App\Models\User|null $committedBy
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
        'number_employees',
        'number_masters',
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

    protected $with = ['craft', 'users', 'freelancer', 'service_provider', 'committedBy'];

    protected $appends = [
        'break_formatted',
        'user_count',
        'empty_user_count',
        'empty_master_count',
        'master_count',
        'currentCount',
        'maxCount',
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
        return $this->belongsTo(Event::class)->without(['series']);
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Craft::class)->without(['users']);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'user_id')
            ->withPivot(['is_master', 'shift_count'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean'])
            ->without(['calender_settings']);
    }

    public function freelancer(): BelongsToMany
    {
        return $this->belongsToMany(Freelancer::class, 'shifts_freelancers', 'shift_id', 'freelancer_id')
            ->withPivot(['is_master', 'shift_count'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean']);
    }

    //@todo: fix phpcs error - refactor function name to serviceProvider
    //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function service_provider(): BelongsToMany
    {
        return $this->belongsToMany(
            ServiceProvider::class,
            'shifts_service_providers',
            'shift_id',
            'service_provider_id'
        )->withPivot(['is_master', 'shift_count'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean'])
            ->without(['contacts']);
    }

    public function shiftsQualifications(): HasMany
    {
        return $this->hasMany(ShiftsQualifications::class);
    }

    public function getCurrentCountAttribute(): int
    {
        $shiftId = $this->id;

        // Zählen der Benutzer, die dem Shift zugeordnet sind
        $userCount = DB::table('shift_user')
            ->where('shift_id', $shiftId)
            ->count();

        // Zählen der Freelancer, die dem Shift zugeordnet sind
        $freelancerCount = DB::table('shifts_freelancers')
            ->where('shift_id', $shiftId)
            ->count();

        // Zählen der Dienstleister, die dem Shift zugeordnet sind
        $serviceProviderCount = DB::table('shifts_service_providers')
            ->where('shift_id', $shiftId)
            ->count();

        // Summe der gezählten Werte
        return $userCount + $freelancerCount + $serviceProviderCount;
    }

    public function getMaxCountAttribute(): int
    {
        return $this->number_employees + $this->number_masters;
    }

    public function getMasterCountAttribute(): float
    {
        return $this->getWorkerCount(true);
    }

    public function getEmptyMasterCountAttribute(): float
    {
        $masterCount = $this->number_masters - $this->getWorkerCount(true);
        return $masterCount;
    }

    public function getEmptyUserCountAttribute(): float
    {
        return $this->number_employees - $this->getWorkerCount();
    }

    protected function getWorkerCount($is_master = false): float
    {

        $users = $this->users()
            ->wherePivot('is_master', $is_master)
            ->without(['calender_settings'])
            ->get();

        $serviceProviders = $this->service_provider()
            ->wherePivot('is_master', $is_master)
            ->get();

        $freelancers = $this->freelancer()
            ->wherePivot('is_master', $is_master)
            ->get();

        $totalCount = 0;

        foreach ($users as $user) {
            $totalCount += 1 / $user->pivot->shift_count;
        }

        foreach ($serviceProviders as $serviceProvider) {
            $totalCount += 1 / $serviceProvider->pivot->shift_count;
        }

        foreach ($freelancers as $freelancer) {
            $totalCount += 1 / $freelancer->pivot->shift_count;
        }

        return $totalCount;
    }


    public function getUserCountAttribute(): float
    {
        return $this->getWorkerCount();
    }

    public function getHistoryAttribute(): Collection
    {
        return $this->historyChanges()->sortByDesc('created_at');
    }

    public function getMastersAttribute(): Collection
    {
        $masterUsers = $this->users()->wherePivot('is_master', true)->without(['calender_settings'])->get();
        $masterFreelancers = $this->freelancer()->wherePivot('is_master', true)->get();
        $masterServiceProviders = $this->service_provider()->wherePivot('is_master', true)->get();

        return $masterUsers->concat((array)$masterFreelancers)->concat((array)$masterServiceProviders);
    }


    public function getEmployeesAttribute(): Collection
    {
        return $this->users()
            ->wherePivot('is_master', false)
            ->without(['calender_settings'])
            ->get()
            ->merge($this->freelancer()->wherePivot('is_master', false)->get())
            ->merge($this->service_provider()->wherePivot('is_master', false)->get());
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

    public function scopeIsCommitted(Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_committed', true);
    }
}
