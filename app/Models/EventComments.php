<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property int $user_id
 * @property string $comment
 * @property bool $is_admin_comment
 * @property Carbon $created_at
 * @property string $updated_at
 */
class EventComments extends Model
{
    use HasFactory;

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
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
