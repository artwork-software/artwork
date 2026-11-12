<?php

namespace Artwork\Modules\AiAssistant\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein Gespraech mit dem KI-Assistenten (core-chat-historie; Konzept C3 §2).
 *
 * @property string $id
 * @property int $user_id
 * @property string $title
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $last_turn_at
 */
class AiConversation extends Model
{
    use HasUuids;

    public const STATUS_ACTIVE = 'active';

    /** Wird erst mit W2 gesetzt. */
    public const STATUS_AWAITING_CONFIRMATION = 'awaiting_confirmation';

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'last_turn_at',
    ];

    protected $casts = [
        'last_turn_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AiConversationTurn, $this> */
    public function turns(): HasMany
    {
        return $this->hasMany(AiConversationTurn::class, 'conversation_id');
    }
}
