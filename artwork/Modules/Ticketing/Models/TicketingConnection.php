<?php

namespace Artwork\Modules\Ticketing\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Die Verbindung dieser Instanz zu ihrem Haus in Artwork-Tickets — es gibt höchstens eine.
 *
 * @property int $id
 * @property string $tickets_url
 * @property string $organization_id
 * @property string $organization_slug
 * @property string $dashboard_url
 * @property string $api_key
 * @property string $oauth_client_id
 * @property int|null $connected_by_user_id
 * @property Carbon|null $customers_synced_at
 * @property Carbon|null $customers_sync_started_at
 * @property string|null $customers_sync_cursor
 * @property string|null $customers_sync_error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $connectedBy
 */
class TicketingConnection extends Model
{
    protected $table = 'ticketing_connections';

    protected $fillable = [
        'tickets_url',
        'organization_id',
        'organization_slug',
        'dashboard_url',
        'api_key',
        'oauth_client_id',
        'connected_by_user_id',
        'customers_synced_at',
        'customers_sync_started_at',
        'customers_sync_cursor',
        'customers_sync_error',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'customers_synced_at' => 'datetime',
        'customers_sync_started_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    /** @return BelongsTo<User, $this> */
    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by_user_id', 'id', 'connectedBy');
    }
}
