<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function Termwind\render;

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
        'name', 'display_name', 'type'
    ];

    public function shifts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shifts_freelancers', 'freelancer_id', 'shift_id')->withPivot(['is_master'])->orderByPivot('is_master', 'desc')->withCasts(['is_master' => 'boolean']);
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
