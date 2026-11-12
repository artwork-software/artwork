<?php

namespace Artwork\Modules\AiAssistant\Models;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine ausgefuehrte KI-Aktion mit ihren Objekten (core-undo; Konzept W3 §1).
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $conversation_id
 * @property string $tool
 * @property string $status
 * @property \Illuminate\Support\Carbon $executed_at
 * @property \Illuminate\Support\Carbon $undo_until
 * @property \Illuminate\Support\Carbon|null $reverted_at
 */
class AiActionBatch extends Model
{
    use HasUuids;

    public const STATUS_EXECUTED = 'executed';

    public const STATUS_REVERTED = 'reverted';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'tool',
        'status',
        'executed_at',
        'undo_until',
        'reverted_at',
    ];

    protected $casts = [
        'executed_at' => 'datetime',
        'undo_until' => 'datetime',
        'reverted_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<AiActionBatchItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AiActionBatchItem::class, 'batch_id');
    }
}
