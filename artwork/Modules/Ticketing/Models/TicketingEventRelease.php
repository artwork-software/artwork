<?php

namespace Artwork\Modules\Ticketing\Models;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Die Preisklassen eines Termins für den Ticketshop — je Klasse Plätze und Preis — sowie sein
 * Freigabestand. zone_key null heißt: eine Klasse nur für diesen Termin, die der Raum nicht kennt.
 *
 * @property int $id
 * @property int $event_id
 * @property list<array{zone_key: string|null, name: string, price_cents: int, quota: int}> $classes
 * @property string $state
 * @property string|null $tickets_date_id
 * @property \Carbon\Carbon|null $released_at
 * @property int|null $released_by_user_id
 */
class TicketingEventRelease extends Model
{
    public const STATE_DRAFT = 'draft';
    public const STATE_RELEASED = 'released';

    protected $fillable = [
        'event_id',
        'classes',
        'state',
        'tickets_date_id',
        'released_at',
        'released_by_user_id',
    ];

    protected $casts = [
        'classes' => 'array',
        'released_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }
}
