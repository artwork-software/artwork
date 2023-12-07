<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Freelancer extends Model
{
    use HasFactory;

    /**
     * @var string[]
     */
    protected $fillable = [
        'position',
        'profile_image',
        'first_name',
        'last_name',
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
    protected $appends = [
        'name', 'display_name', 'type', 'profile_photo_url'
    ];

    /**
     * @return BelongsToMany
     */
    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(
            Shift::class,
            'shifts_freelancers',
            'freelancer_id',
            'shift_id'
        )->withPivot(['is_master'])
            ->orderByPivot('is_master', 'desc')
            ->withCasts(['is_master' => 'boolean'])
            ->without(['users', 'freelancer']);
    }

    /**
     * @return string
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_image ?
            $this->profile_image :
            'https://ui-avatars.com/api/?name=' . $this->name . '&color=7F9CF5&background=EBF4FF';
    }

    /**
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * @return string
     */
    public function getTypeAttribute(): string
    {
        return 'freelancer';
    }

    /**
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->last_name . ', ' . $this->first_name;
    }

    /**
     * @return HasMany
     */
    public function vacations(): HasMany
    {
        return $this->hasMany(FreelancerVacation::class);
    }

    /**
     * @return BelongsToMany
     */
    public function assigned_crafts(): BelongsToMany
    {
        return $this->belongsToMany(Craft::class, 'freelancer_assigned_crafts');
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
