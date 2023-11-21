<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Artwork\Modules\Room\Models\Room;

class ShiftFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_shift_filter');
    }

    public function event_types()
    {
        return $this->belongsToMany(EventType::class, 'event_type_shift_filter');
    }


}
