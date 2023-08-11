<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function Termwind\render;

/**
 * App\Models\Freelancer
 * @property int $id
 * @property string|null $position
 * @property string|null $profile_image
 * @property string $first_name
 * @property string $last_name
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
 * @property-read string $display_name
 * @property-read string $name
 * @property-read string $type
 * @property-read string $profile_photo_url
 * @property-read Collection|Shift[] $shifts
 * @property-read int|null $shifts_count
 */
class Freelancer extends Model
{
    use HasFactory;


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
    ];

    protected $appends = [
        'name', 'display_name', 'type', 'profile_photo_url'
    ];

    public function shifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shifts_freelancers', 'freelancer_id', 'shift_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean']);
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_image ? $this->profile_image : 'https://ui-avatars.com/api/?name=' . $this->name . '&color=7F9CF5&background=EBF4FF';
    }

    public function getNameAttribute(){
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

    public function vacations(): HasMany
    {
        return $this->hasMany(FreelancerVacation::class);
    }

    public function getShiftsAttribute($start, $end): Collection
    {
        return $this->shifts()
            ->with(['event' => function($query) use ($start, $end){
                $query->whereBetween('start_time', [$start, $end])
                    ->whereBetween('end_time', [$start, $end]);
            }, 'event.room'])
            ->get()
            ->makeHidden(['allUsers'])
            ->groupBy(function ($shift) {
                return $shift->event?->days_of_event;
            });
    }

}
