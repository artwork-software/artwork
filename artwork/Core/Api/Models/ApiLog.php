<?php

namespace Artwork\Core\Api\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Token;

/**
 * Zugriffsprotokoll der Maschinen-API.
 *
 * @property int $id
 * @property string|null $passport_token_id Null bei fehlgeschlagener Authentifizierung
 * @property string $url
 * @property string $method
 * @property string $ip
 * @property string $user_agent
 * @property int|null $response_status
 * @property int|null $duration_ms
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ApiLog extends Model
{
    use Prunable;

    protected $table = 'api_log';

    protected $guarded = [];

    protected $casts = [
        'response_status' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class, 'passport_token_id');
    }

    /**
     * Die Tabelle wuchs bisher unbegrenzt. 90 Tage decken Fehlersuche und Missbrauchsanalyse ab.
     *
     * Achtung: model:prune findet nur Models unter app/Models automatisch — dieses Model ist deshalb
     * in app/Console/Kernel.php explizit eingetragen.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }
}
