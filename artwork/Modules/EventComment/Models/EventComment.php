<?php

namespace Artwork\Modules\EventComment\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property string $comment
 * @property bool $is_admin_comment
 * @property Event $event
 * @property User $user
 * @property Carbon $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class EventComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'user_id',
        'comment',
        'is_admin_comment'
    ];

    protected $casts = [
        'is_admin_comment' => 'boolean',
        'created_at' => 'datetime: d.m.Y H:i'
    ];

    protected $with = [
        'user'
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class,
            'event_id',
            'id',
            'events'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id',
            'users'
        );
    }
}
