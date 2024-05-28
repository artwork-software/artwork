<?php

namespace Artwork\Modules\UserShiftCalendarAbo\Models;

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
 * @property bool $enable_notification
 * @property int $notification_time
 * @property string $notification_time_unit
 * @property string $created_at
 * @property string $updated_at
 * @property-read User $user
 * @property-read string $calendar_abo_url
 */
class UserShiftCalendarAbo extends Model
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
        'enable_notification',
        'notification_time',
        'notification_time_unit',
    ];

    protected $casts = [
        'date_range' => 'boolean',
        'specific_event_types' => 'boolean',
        'event_types' => 'array',
        'enable_notification' => 'boolean',
        'notification_time' => 'integer',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    protected $appends = [
        'calendar_abo_url',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    public function getCalendarAboUrlAttribute(): string
    {
        return route('user-shift-calendar-abo.show', $this->calendar_abo_id);
    }
}
