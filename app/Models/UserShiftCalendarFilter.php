<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserShiftCalendarFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_types',
        'rooms'
    ];

    protected $casts = [
        'event_types' => 'array',
        'rooms' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
