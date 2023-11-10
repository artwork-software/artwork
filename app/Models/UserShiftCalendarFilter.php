<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property array $event_types
 * @property array $rooms
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserShiftCalendarFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
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
