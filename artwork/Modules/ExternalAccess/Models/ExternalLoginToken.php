<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Carbon\Carbon;
use Database\Factories\Artwork\Modules\ExternalAccess\Models\ExternalLoginTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $external_access_id
 * @property string $token_hash
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 * @property string|null $ip_address
 * @property string|null $user_agent
 */
class ExternalLoginToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_access_id',
        'token_hash',
        'expires_at',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExternalLoginTokenFactory
    {
        return ExternalLoginTokenFactory::new();
    }

    public function externalAccess(): BelongsTo
    {
        return $this->belongsTo(ExternalAccess::class, 'external_access_id', 'id', 'externalAccess');
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
