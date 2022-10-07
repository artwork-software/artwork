<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'isLoud',
        'isNotLoud',
        'hasAudience',
        'hasNoAudience',
        'adjoiningNoAudience',
        'adjoiningNotLoud',
        'allDayFree',
        'showAdjoiningRooms',
        'user_id'
    ];

    protected $casts = [
        'isLoud' => 'boolean',
        'isNotLoud' => 'boolean',
        'hasAudience' => 'boolean',
        'hasNoAudience' => 'boolean',
        'adjoiningNoAudience' => 'boolean',
        'adjoiningNotLoud' => 'boolean',
        'allDayFree' => 'boolean',
        'showAdjoiningRooms' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room_categories()
    {
        return $this->belongsToMany(RoomCategory::class, 'filter_room_category');
    }

    public function room_attributes()
    {
        return $this->belongsToMany(RoomAttribute::class, 'filter_room_attribute');
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'filter_room');
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class, 'area_filter');
    }

    public function event_types()
    {
        return $this->belongsToMany(EventType::class, 'event_type_filter');
    }


}
