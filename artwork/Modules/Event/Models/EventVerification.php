<?php

namespace Artwork\Modules\Event\Models;

use Artwork\Core\Casts\TranslatedDateTimeCast;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class EventVerification
 *
 * @property int $id
 * @property string $uuid
 * @property int $event_id
 * @property int|null $verifier_id
 * @property string|null $verifier_type
 * @property string|null $status
 * @property string|null $rejection_reason
 * @property string|null $created_at übersetzter Anzeige-String (TranslatedDateTimeCast), kein Carbon
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property \Artwork\Modules\Event\Models\Event $event
 * @property \Illuminate\Database\Eloquent\Model $verifier
 * @property \Artwork\Modules\User\Models\User $requester
 */
class EventVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'event_id',
        'verifier_id',
        'verifier_type',
        'status',
        'rejection_reason',
        'request_user_id',
    ];

    protected $casts = [
        'created_at' => TranslatedDateTimeCast::class,
    ];

    /**
     * created_at ist für die Anzeige zu einem übersetzten String gecastet; für Payloads wird der
     * Rohwert als ISO-8601 gebraucht.
     */
    public function createdAtIso8601(): ?string
    {
        $rawCreatedAt = $this->getRawOriginal('created_at');

        return $rawCreatedAt === null ? null : Carbon::parse($rawCreatedAt)->toIso8601String();
    }

    public function verifier(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id', 'events');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'request_user_id', 'id', 'requester');
    }
}
