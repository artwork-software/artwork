<?php

namespace App\Models;

use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'profile_image',
        'provider_name',
        'email',
        'phone_number',
        'street',
        'zip_code',
        'location',
        'can_master',
        'note',
        'salary_per_hour',
        'salary_description',
        'work_name',
        'work_description',
        'can_work_shifts'
    ];

    /**
     * @var string[]
     */
    protected $with = ['contacts'];

    /**
     * @var string[]
     */
    protected $appends = ['name', 'type', 'profile_photo_url'];

    /**
     * @return HasMany
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(ServiceProviderContacts::class);
    }

    /**
     * @return BelongsToMany
     */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(
            Shift::class,
            'shifts_service_providers',
            'service_provider_id',
            'shift_id'
        )->withPivot(['is_master'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean']);
    }

    /**
     * @return BelongsToMany
     */
    public function assigned_crafts(): BelongsToMany
    {
        return $this->belongsToMany(Craft::class, 'service_provider_assigned_crafts');
    }

    /**
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->provider_name;
    }

    /**
     * @return Collection
     */
    public function getShiftsAttribute(): Collection
    {
        return $this->shifts()
            ->without(['craft', 'users', 'event.project.shiftRelevantEventTypes'])
            ->with(['event.room'])
            ->get()
            ->makeHidden(['allUsers'])
            ->groupBy(function ($shift) {
                return $shift->event->days_of_event;
            });
    }


    /**
     * @return string
     */
    public function getTypeAttribute(): string
    {
        return 'service_provider';
    }

    /**
     * @return string
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_image ?
            $this->profile_image :
            'https://ui-avatars.com/api/?name=' . $this->provider_name[0] . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return float|int
     */
    public function plannedWorkingHours($startDate, $endDate): float|int
    {
        $shiftsInDateRange = $this->shifts()
            ->whereBetween('event_start_day', [$startDate, $endDate])
            ->get();

        $plannedWorkingHours = 0;

        foreach ($shiftsInDateRange as $shift) {
            $shiftStart = Carbon::parse($shift->start); // Parse the start time
            $shiftEnd = Carbon::parse($shift->end);     // Parse the end time
            $breakMinutes = $shift->break_minutes;

            $shiftDuration = ($shiftEnd->diffInMinutes($shiftStart) - $breakMinutes) / 60;
            $plannedWorkingHours += $shiftDuration;
        }

        return $plannedWorkingHours;
    }
}
