<?php

namespace Artwork\Modules\Webhook\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Webhook\Enums\WebhookDeliveryStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ein Zustellversuchs-Protokoll je Endpunkt und Ereignis.
 *
 * @property int $id
 * @property int $webhook_endpoint_id
 * @property string $event_name
 * @property array<string, mixed> $payload
 * @property int $attempt
 * @property WebhookDeliveryStatus $status
 * @property int|null $response_status
 * @property string|null $error
 * @property Carbon|null $next_retry_at
 * @property Carbon|null $delivered_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WebhookDelivery extends Model
{
    use Prunable;

    protected $table = 'webhook_deliveries';

    protected $fillable = [
        'webhook_endpoint_id',
        'event_name',
        'payload',
        'attempt',
        'status',
        'response_status',
        'error',
        'next_retry_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'status' => WebhookDeliveryStatus::class,
        'attempt' => 'integer',
        'response_status' => 'integer',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /**
     * Erfolgreiche Zustellungen sind nach 30 Tagen uninteressant, fehlgeschlagene bleiben länger,
     * weil sie für die Fehlersuche beim Empfänger gebraucht werden.
     *
     * Achtung: model:prune findet nur Models unter app/Models automatisch — dieses Model ist
     * deshalb in app/Console/Kernel.php explizit eingetragen.
     */
    public function prunable(): Builder
    {
        return static::query()
            ->where(function (Builder $query): void {
                $query->where('status', WebhookDeliveryStatus::SUCCESS->value)
                    ->where('created_at', '<=', now()->subDays(30));
            })
            ->orWhere('created_at', '<=', now()->subDays(90));
    }
}
