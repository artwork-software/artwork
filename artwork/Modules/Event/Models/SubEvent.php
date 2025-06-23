<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property string $eventName
 * @property string $description
 * @property string $start_time
 * @property string $end_time
 * @property bool $audience
 * @property bool $is_loud
 * @property bool $allDay
 * @property int $event_type_id
 * @property int $user_id
 * @property EventType $type
 * @property Event $event
 * @property User $creator
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class SubEvent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'eventName',
        'description',
        'start_time',
        'end_time',
        'event_type_id',
        'user_id',
        'audience',
        'is_loud',
        'allDay'
    ];

    protected $casts = [
        'audience' => 'boolean',
        'is_loud' => 'boolean',
        'allDay' => 'boolean'
    ];

    protected $with = ['type', 'creator', 'eventProperties'];

    protected $appends = ['formattedDates'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(
            EventType::class,
            'event_type_id',
            'id',
            'event_types'
        );
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class,
            'event_id',
            'id',
            'events'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'users'
        );
    }

    public function eventProperties(): BelongsToMany
    {
        return $this->belongsToMany(EventProperty::class);
    }

    public function getFormattedDatesAttribute(){
        return [
            'start_time' => Carbon::parse($this->start_time)->format('H:i'),
            'end_time' => Carbon::parse($this->end_time)->format('H:i'),
            'start_date' => Carbon::parse($this->start_time)->format('d.m.Y'),
            'end_date' => Carbon::parse($this->end_time)->format('d.m.Y'),
            'start_date_time' => Carbon::parse($this->start_time)->format('d.m.Y H:i'),
            'end_date_time' => Carbon::parse($this->end_time)->format('d.m.Y H:i'),
            'is_same_day' => Carbon::parse($this->start_time)->isSameDay(Carbon::parse($this->end_time)),
        ];
    }
}
