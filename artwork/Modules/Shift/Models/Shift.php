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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Shift
 * @property int $id
 * @property int $event_id
 * @property string $start
 * @property string $end
 * @property int $break_minutes
 * @property int $craft_id
 * @property int $number_employees
 * @property int $number_masters
 * @property string|null $description
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
 */
class Shift extends Model
{
    use HasFactory, HasChangesHistory;

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
        'event_end_day'
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
        'is_committed' => 'boolean'
    ];

    protected $with = ['craft'];

    protected $appends = ['break_formatted', 'user_count', 'empty_user_count', 'empty_master_count', 'master_count', 'allUsers', 'currentCount', 'maxCount', 'infringement'];

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class)->without(['series']);
    }

    public function craft(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Craft::class)->without(['users']);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'user_id')
            ->withPivot(['is_master', 'shift_count'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean'])
            ->without(['calender_settings']);
    }

    public function freelancer(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Freelancer::class, 'shifts_freelancers', 'shift_id', 'freelancer_id')
            ->withPivot(['is_master', 'shift_count'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean']);
    }

    public function service_provider(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class, 'shifts_service_providers', 'shift_id', 'service_provider_id')
            ->withPivot(['is_master', 'shift_count'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean'])
            ->without(['contacts']);
    }

    public function getCurrentCountAttribute(): int
    {
        return $this->users->count() + $this->freelancer->count() + $this->service_provider->count();
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

    public function getHistoryAttribute(): \Illuminate\Support\Collection
    {
        return $this->historyChanges();
    }

    public function getMastersAttribute(): \Illuminate\Support\Collection
    {
        $masterUsers = $this->users()->wherePivot('is_master', true)->without(['calender_settings'])->get();
        $masterFreelancers = $this->freelancer()->wherePivot('is_master', true)->get();
        $masterServiceProviders = $this->service_provider()->wherePivot('is_master', true)->get();

        return $masterUsers->concat((array)$masterFreelancers)->concat((array)$masterServiceProviders);
    }


    public function getEmployeesAttribute(): \Illuminate\Support\Collection
    {
        return $this->users()->wherePivot('is_master', false)->without(['calender_settings'])->get()->merge($this->freelancer()->wherePivot('is_master', false)->get())->merge($this->service_provider()->wherePivot('is_master', false)->get());
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


    public function getAllUsersAttribute(): array
    {
        return [
            'users' => $this->users,
            'freelancers' => $this->freelancer,
            'service_providers' => $this->service_provider,
        ];
    }

}
