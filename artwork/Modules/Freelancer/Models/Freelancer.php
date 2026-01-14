<?php

namespace Artwork\Modules\Freelancer\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Availability\Models\Available;
use Artwork\Modules\Availability\Models\HasAvailability;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\DayService\Models\DayServiceable;
use Artwork\Modules\DayService\Models\Traits\CanHasDayServices;
use Artwork\Modules\IndividualTimes\Models\Traits\HasIndividualTimes;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\Traits\HasShiftPlanComments;
use Artwork\Modules\Shift\Models\Traits\HasShifts;
use Artwork\Modules\Vacation\Models\GoesOnVacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property-read string $name
 * @property-read string $display_name
 * @property-read string $type
 * @property-read string $profile_photo_url
 * @property-read array<int> $assigned_craft_ids
 * @property-read Collection<int, Shift> $shifts
 * @property-read Collection<int, ShiftQualification> $shiftQualifications
 * @property-read Collection<int, Craft> $assignedCrafts
 * @property-read Collection<int, Craft> $managingCrafts
 * @property-read Collection<int, \Artwork\Modules\Shift\Models\GlobalQualification> $globalQualifications
 *
 */
class Freelancer extends Model implements Vacationer, Available, DayServiceable, Employable
{
    use HasFactory;
    use GoesOnVacation;
    use HasAvailability;
    use CanHasDayServices;
    use HasIndividualTimes;
    use HasShiftPlanComments;
    use HasShifts;
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


    public function getProfilePhotoUrlAttribute(): string
    {
        $isUrl = filter_var($this->profile_image, FILTER_VALIDATE_URL);
        if ($isUrl) {
            return $this->profile_image;
        }
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : route('generate-avatar-image', ['letters' => $this->first_name[0] . $this->last_name[0]]);
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

}
