<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class EventVerification
 *
 * @property int $id
 * @property int $event_id
 * @property int|null $verifier_id
 * @property string|null $verifier_type
 * @property string|null $status
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property \Artwork\Modules\Event\Models\Event $event
 * @property \Illuminate\Database\Eloquent\Model $verifier
 * @property \Artwork\Modules\User\Models\User $requester
 */
class EventVerification extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'event_id', 'verifier_id', 'verifier_type', 'status', 'rejection_reason', 'request_user_id'];

    protected $casts = [
        'created_at' => TranslatedDateTimeCast::class,
    ];

    public function verifier(): MorphTo
    {
        return $this->morphTo();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id', 'events');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id', 'id', 'requester');
    }
}
