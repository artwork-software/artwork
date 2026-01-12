<?php

namespace Artwork\Modules\ServiceProvider\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Contacts\Models\Traits\HasContacts;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\DayService\Models\DayServiceable;
use Artwork\Modules\DayService\Models\Traits\CanHasDayServices;
use Artwork\Modules\IndividualTimes\Models\Traits\HasIndividualTimes;
use Artwork\Modules\ServiceProvider\Models\ServiceProviderContacts;
use Artwork\Modules\Shift\Contracts\Employable;
use Artwork\Modules\Shift\Models\Traits\HasShiftPlanComments;
use Artwork\Modules\Shift\Models\Traits\HasShifts;
use Artwork\Modules\Vacation\Models\GoesOnVacation;
use Artwork\Modules\Vacation\Models\Vacationer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

/**
 * @property int $id
 * @property string $profile_image
 * @property string $provider_name
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
class ServiceProvider extends Model implements Vacationer, DayServiceable, Employable
{
    use HasFactory;
    use CanHasDayServices;
    use HasIndividualTimes;
    use HasShiftPlanComments;
    use HasShifts;
    use GoesOnVacation;
    use Searchable;
    use HasContacts;

    protected $fillable = [
        'profile_image',
        'provider_name',
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
        'can_work_shifts',
        'type_of_provider'
    ];

    protected $appends = [
        'name',
        'type',
        'profile_photo_url',
        'assigned_craft_ids',
    ];

    protected $casts = [
        'can_work_shifts' => 'boolean'
    ];

    public function oldContacts(): HasMany
    {
        return $this->hasMany(ServiceProviderContacts::class);
    }


    public function getNameAttribute(): string
    {
        return $this->provider_name;
    }

    public function getTypeAttribute(): string
    {
        return 'service_provider';
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        $isUrl = filter_var($this->profile_image, FILTER_VALIDATE_URL);
        if ($isUrl) {
            return $this->profile_image;
        }
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : route('generate-avatar-image', ['letters' => $this->provider_name[0] ?? 'S']);
    }

}
