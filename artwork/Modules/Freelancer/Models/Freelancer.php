<?php

namespace Artwork\Modules\Freelancer\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Availability\Models\HasAvailability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\DayService\Models\DayServiceable;
use Artwork\Modules\DayService\Models\Traits\CanHasDayServices;
use Artwork\Modules\IndividualTimes\Models\Traits\HasIndividualTimes;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\Shift\Models\ShiftFreelancer;
use Artwork\Modules\Shift\Models\Traits\HasShiftPlanComments;
use Artwork\Modules\Shift\Models\FreelancerShiftQualification;
use Artwork\Modules\Shift\Models\ShiftQualification;
use Artwork\Modules\Vacation\Models\GoesOnVacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $position
 * @property string $profile_image
 * @property string $first_name
 * @property string $last_name
 * @property string $work_name
 * @property string $work_description
 * @property string $email
 * @property string $phone_number
 * @property string $street
 * @property string $zip_code
 * @property string $location
 * @property string $note
 * @property int $salary_per_hour
 * @property string $salary_description
 * @property string $created_at
 * @property string $updated_at
 * @property int $can_work_shifts
 */
class Freelancer extends Model implements Vacationer, Available, DayServiceable
{
    use HasFactory;
    use GoesOnVacation;
    use HasAvailability;
    use CanHasDayServices;
    use HasIndividualTimes;
    use HasShiftPlanComments;
    use Searchable;

    /**
     * @var string[]
     */
    //phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        'note',
        'salary_per_hour',
        'salary_description',
        'work_name',
        'work_description',
        'can_work_shifts'
    ];

    protected $appends = [
        'name',
        'display_name',
        'type',
        'profile_photo_url',
        'assigned_craft_ids',
    ];

    protected $casts = [
        'can_work_shifts' => 'boolean'
    ];

    public function shifts(): BelongsToMany
    {
        return $this
            ->belongsToMany(Shift::class, 'shifts_freelancers')
            ->using(ShiftFreelancer::class)
            ->withPivot('id', 'shift_qualification_id', 'craft_abbreviation');
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_image
            ?: route('generate-avatar-image', ['letters' => $this->first_name[0] . $this->last_name[0]]);
    }

    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getTypeAttribute(): string
    {
        return 'freelancer';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->last_name . ', ' . $this->first_name;
    }

    public function assignedCrafts(): morphToMany
    {
        return $this->morphToMany(Craft::class, 'craftable');
    }

    public function managingCrafts(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craft_manager');
    }

    /**
     * @return array<int>
     */
    public function getAssignedCraftIdsAttribute(): array
    {
        return $this->assignedCrafts()->pluck('crafts.id')->toArray();
    }

    public function shiftQualifications(): BelongsToMany
    {
        return $this
            ->belongsToMany(ShiftQualification::class, 'freelancer_shift_qualifications')
            ->using(FreelancerShiftQualification::class);
    }

    public function getShiftIdsBetweenStartDateAndEndDate(
        Carbon $startDate,
        Carbon $endDate
    ): Collection {
        return $this->shifts()->eventStartDayAndEventEndDayBetween($startDate, $endDate)->pluck('shifts.id');
    }

    //@todo refactor this too
    public function plannedWorkingHours($startDate, $endDate): float|int
    {
        $shiftsInDateRange = $this->shifts()
            ->whereBetween('event_start_day', [$startDate, $endDate])
            ->get();

        $plannedWorkingHours = 0;

        $individualTimes = $this->individualTimes()
            ->individualByDateRange($startDate, $endDate)->sum('working_time_minutes');

        foreach ($shiftsInDateRange as $shift) {
            $shiftStart = $shift->start_date->format('Y-m-d') . ' ' . $shift->start; // Parse the start time
            $shiftEnd =  $shift->end_date->format('Y-m-d') . ' ' . $shift->end;    // Parse the end time
            $breakMinutes = $shift->break_minutes;

            $shiftStart = Carbon::parse($shiftStart);
            $shiftEnd = Carbon::parse($shiftEnd);


            $shiftDuration = (($shiftEnd->diffInRealMinutes($shiftStart) + $individualTimes) - $breakMinutes) / 60;
            $plannedWorkingHours += $shiftDuration;
        }


        return $plannedWorkingHours;
    }

    public function scopeCanWorkShifts(Builder $builder): Builder
    {
        return $builder->where('can_work_shifts', true);
    }

    public function craftsToManage(): MorphToMany
    {
        return $this->morphToMany(Craft::class, 'craft_manager');
    }

    /**
     * @return array<int, int>
     */
    public function getManagingCraftIds(): array
    {
        return $this->craftsToManage()->pluck('id')->toArray();
    }
}
