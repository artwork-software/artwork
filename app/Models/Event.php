<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_time',
        'end_time',
        'occupancy_option',
        'audience',
        'is_loud',
        'event_type_id',
        'room_id',
        'project_id',
        'user_id'
    ];

    protected $casts = [
        'is_loud' => 'boolean',
        'audience' => 'boolean',
        'occupancy_option' => 'boolean'
    ];

    public function event_type()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


}
