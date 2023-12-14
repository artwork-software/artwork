<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @property string $created_at
 * @property string $updated_at
 */
class SubEvents extends Model
{
    use HasFactory;

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

    protected $with = ['type', 'creator'];

    public function type(): BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
