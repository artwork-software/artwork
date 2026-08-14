<?php

namespace Artwork\Modules\Webhook\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ein Empfänger für ausgehende Ereignisse.
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property array<int, string> $subscribed_events
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WebhookEndpoint extends Model
{
    protected $table = 'webhook_endpoints';

    protected $fillable = [
        'name',
        'url',
        'secret',
        'subscribed_events',
        'is_active',
    ];

    protected $casts = [
        // Der Cast hält das Signaturgeheimnis verschlüsselt in der Datenbank. Wer es per Raw-Query
        // liest, bekommt Chiffretext — Zugriffe müssen über das Model laufen.
        'secret' => 'encrypted',
        'subscribed_events' => 'array',
        'is_active' => 'boolean',
    ];

    // Das Geheimnis verlässt den Server nur einmalig bei der Erstellung.
    protected $hidden = [
        'secret',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function isSubscribedTo(string $eventName): bool
    {
        return in_array($eventName, $this->subscribed_events, true);
    }
}
