<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property Event $event_id
 * @property string $eventName
 * @property string $description
 * @property \DateTime $start_time
 * @property \DateTime $end_time
 * @property EventType $event_type_id
 * @property User $user_id
 * @property boolean $audience
 * @property boolean $is_loud
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
        'is_loud'
    ];


    protected $casts = [
        'audience' => 'boolean',
        'is_loud' => 'boolean',
    ];

    protected $with = ['type', 'creator'];

    public function type(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function event(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
