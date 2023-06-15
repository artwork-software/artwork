<?php

namespace App\Models;

use App\Casts\TimeWithoutSeconds;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'start',
        'end',
        'break_minutes',
        'craft_id',
        'number_employees',
        'number_masters',
        'description',
    ];

    protected $casts = [
        'start' => TimeWithoutSeconds::class,
        'end' => TimeWithoutSeconds::class,
    ];

    protected $with = ['craft'];

    protected $appends = ['break_formatted', 'user_count', 'empty_user_count', 'empty_master_count', 'master_count', 'allUsers', 'currentCount', 'maxCount'];

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
        return $this->belongsToMany(User::class, 'shift_user', 'shift_id', 'user_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean'])->without(['calender_settings']);
    }

    public function freelancer(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Freelancer::class, 'shifts_freelancers', 'shift_id', 'freelancer_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean']);
    }

    public function service_provider(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class, 'shifts_service_providers', 'shift_id', 'service_provider_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean'])->without(['contacts']);
    }

    public function getCurrentCountAttribute(): int
    {
        return $this->users()->count() + $this->freelancer()->count() + $this->service_provider()->count();
    }

    public function getMaxCountAttribute(): int
    {
        return $this->number_employees + $this->number_masters;
    }

    public function getMasterCountAttribute(): int
    {
        $freelancerCount = $this->freelancer()->wherePivot('is_master', true)->count();
        $serviceProviderCount = $this->service_provider()->wherePivot('is_master', true)->count();
        $userCount = $this->users()->wherePivot('is_master', true)->count();
        return $freelancerCount + $serviceProviderCount + $userCount;
    }

    public function getEmptyMasterCountAttribute(): int
    {
        $masterCount = $this->users()->wherePivot('is_master', true)->count();
        $masterFreelancerCount = $this->freelancer()->wherePivot('is_master', true)->count();
        $masterServiceProviderCount = $this->service_provider()->wherePivot('is_master', true)->count();

        $sumCount = $masterCount + $masterFreelancerCount + $masterServiceProviderCount;
        return $this->number_masters - $sumCount;
    }

    public function getEmptyUserCountAttribute(): int
    {
        $employeeCount = $this->users()->wherePivot('is_master', false)->count();
        $serviceProviderCount = $this->service_provider()->wherePivot('is_master', false)->count();
        $freelancerCount = $this->freelancer()->wherePivot('is_master', false)->count();
        $sumCount = $employeeCount + $serviceProviderCount + $freelancerCount;
        return $this->number_employees - $sumCount;
    }

    public function getUserCountAttribute(): int
    {
        $freelancerCount = $this->freelancer()->wherePivot('is_master', false)->count();
        $serviceProviderCount = $this->service_provider()->wherePivot('is_master', false)->count();
        $userCount = $this->users()->wherePivot('is_master', false)->count();
        $count = $freelancerCount + $serviceProviderCount + $userCount;
        return $count;
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

    public function getAllUsersAttribute(): array
    {
        return [
            'users' => $this->users,
            'freelancers' => $this->freelancer,
            'service_providers' => $this->service_provider,
        ];
    }

}
