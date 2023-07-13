<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'note',
    ];


    protected $with = ['contacts'];

    protected $appends = ['name', 'type'];

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

    public function getShiftsAttribute($start, $end): Collection
    {
        return $this->shifts()
            ->with(['event' => function($query) use ($start, $end){
                $query->whereBetween('start_time', [$start, $end])
                    ->whereBetween('end_time', [$start, $end]);
            }, 'event.room', 'event.project'])
            ->get()
            ->makeHidden(['allUsers'])
            ->groupBy(function ($shift) {
                return $shift->event?->days_of_event;
            });
    }


    public function getTypeAttribute(): string
    {
        return 'service_provider';
    }
}
