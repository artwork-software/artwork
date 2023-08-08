<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use App\Casts\TimeWithoutSeconds;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'is_committed'
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
        return $this->belongsTo(Event::class);
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
        return $this->users()->count() + $this->freelancer()->count() + $this->service_provider()->count();
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
        return $this->number_masters - $this->getWorkerCount(true);
    }

    public function getEmptyUserCountAttribute(): float
    {
        return $this->number_employees - $this->getWorkerCount();
    }

    protected function getWorkerCount($is_master = false): float {

        $employeeCount = $this->users()
            ->wherePivot('is_master', $is_master)
            ->get()
            ->map( fn(User $user) => 1 / $user->pivot->shift_count )
            ->sum();

        $serviceProviderCount = $this->service_provider()
            ->wherePivot('is_master', $is_master)
            ->get()
            ->map( fn(ServiceProvider $serviceProvider) => 1 / $serviceProvider->pivot->shift_count )
            ->sum();

        $freelancerCount = $this->freelancer()
            ->wherePivot('is_master', $is_master)
            ->get()
            ->map( fn(Freelancer $freelancer) => 1 / $freelancer->pivot->shift_count )
            ->sum();

        return $employeeCount + $serviceProviderCount + $freelancerCount;
    }

    public function getUserCountAttribute(): float
    {
        return $this->getWorkerCount();
    }

    public function getHistoryAttribute(): \Illuminate\Support\Collection
    {
        return $this->historyChanges();
    }

    public function getMastersAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->users()->wherePivot('is_master', true)->get()->merge($this->freelancer()->wherePivot('is_master', true)->get())->merge($this->service_provider()->wherePivot('is_master', true)->get());
    }

    public function getEmployeesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->users()->wherePivot('is_master', false)->get()->merge($this->freelancer()->wherePivot('is_master', false)->get())->merge($this->service_provider()->wherePivot('is_master', false)->get());
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
