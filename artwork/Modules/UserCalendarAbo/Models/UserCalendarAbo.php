<?php

namespace Artwork\Modules\UserCalendarAbo\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $calendar_abo_id
 * @property bool $date_range
 * @property string $start_date
 * @property string $end_date
 * @property bool $specific_event_types
 * @property array $event_types
 * @property bool $specific_rooms
 * @property array $selected_rooms
 * @property bool $specific_areas
 * @property array $selected_areas
 * @property bool $enable_notification
 * @property int $notification_time
 * @property string $notification_time_unit
 * @property-read string $calendar_abo_url
 * @property-read User $user
 */
class UserCalendarAbo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'calendar_abo_id',
        'date_range',
        'start_date',
        'end_date',
        'specific_event_types',
        'event_types',
        'specific_rooms',
        'selected_rooms',
        'specific_areas',
        'selected_areas',
        'enable_notification',
        'notification_time',
        'notification_time_unit',
    ];

    protected $casts = [
        'date_range' => 'boolean',
        'specific_event_types' => 'boolean',
        'event_types' => 'array',
        'specific_rooms' => 'boolean',
        'selected_rooms' => 'array',
        'specific_areas' => 'boolean',
        'selected_areas' => 'array',
        'enable_notification' => 'boolean',
        'notification_time' => 'integer',
    ];

    protected $appends = ['calendar_abo_url'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    public function getCalendarAboUrlAttribute(): string
    {
        return route('user-calendar-abo.show', $this->calendar_abo_id);
    }
}
