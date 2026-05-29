<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Database\Factories\Artwork\Modules\ExternalAccess\Models\ExternalInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $external_access_id
 * @property int $invited_by_user_id
 * @property string $source
 * @property int|null $source_reference_id
 * @property Carbon $email_sent_at
 * @property Carbon|null $first_redeemed_at
 */
class ExternalInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_access_id',
        'invited_by_user_id',
        'source',
        'source_reference_id',
        'email_sent_at',
        'first_redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
            'first_redeemed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): ExternalInvitationFactory
    {
        return ExternalInvitationFactory::new();
    }

    public function externalAccess(): BelongsTo
    {
        return $this->belongsTo(ExternalAccess::class, 'external_access_id', 'id', 'externalAccess');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id', 'id', 'invitedBy');
    }
}
