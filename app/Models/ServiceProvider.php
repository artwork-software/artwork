<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ServiceProvider
 * @property int $id
 * @property string|null $profile_image
 * @property string $provider_name
 * @property string|null $email
 * @property string|null $phone_number
 * @property string|null $street
 * @property string|null $zip_code
 * @property string|null $location
 * @property bool $can_master
 * @property string|null $note
 * @property float|null $salary_per_hour
 * @property string|null $salary_description
 * @property string|null $work_name
 * @property string|null $work_description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $name
 * @property-read string $type
 * @property-read string $profile_photo_url
 * @property-read Collection|ServiceProviderContacts[] $contacts
 * @property-read int|null $contacts_count
 * @property-read Collection|Shift[] $shifts
 * @property-read int|null $shifts_count
 */
class ServiceProvider extends Model
{
    use HasFactory;

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
    ];


    protected $with = ['contacts'];

    protected $appends = ['name', 'type', 'profile_photo_url'];

    public function contacts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ServiceProviderContacts::class);
    }

    public function shifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shifts_service_providers', 'service_provider_id', 'shift_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean']);
    }

    public function getNameAttribute(){
        return $this->provider_name;
    }

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


    public function getTypeAttribute(): string
    {
        return 'service_provider';
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_image ? $this->profile_image : 'https://ui-avatars.com/api/?name=' . $this->provider_name[0] . '&color=7F9CF5&background=EBF4FF';
    }

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
