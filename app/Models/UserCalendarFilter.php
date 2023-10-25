<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property boolean $is_loud
 * @property boolean $is_not_loud
 * @property boolean $adjoining_not_loud
 * @property boolean $has_audience
 * @property boolean $has_no_audience
 * @property boolean $adjoining_no_audience
 * @property boolean $show_free_rooms
 * @property boolean $show_adjoining_rooms
 * @property boolean $all_day_free
 * @property array $event_types
 * @property array $rooms
 * @property array $areas
 * @property array $room_attributes
 * @property array $room_categories
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class UserCalendarFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_loud',
        'is_not_loud',
        'adjoining_not_loud',
        'has_audience',
        'has_no_audience',
        'adjoining_no_audience',
        'show_free_rooms',
        'show_adjoining_rooms',
        'all_day_free',
        'event_types',
        'rooms',
        'areas',
        'room_attributes',
        'room_categories',
    ];

    protected $casts = [
        'is_loud' => 'boolean',
        'is_not_loud' => 'boolean',
        'adjoining_not_loud' => 'boolean',
        'has_audience' => 'boolean',
        'has_no_audience' => 'boolean',
        'adjoining_no_audience' => 'boolean',
        'show_free_rooms' => 'boolean',
        'show_adjoining_rooms' => 'boolean',
        'all_day_free' => 'boolean',
        'event_types' => 'array',
        'rooms' => 'array',
        'areas' => 'array',
        'room_attributes' => 'array',
        'room_categories' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
