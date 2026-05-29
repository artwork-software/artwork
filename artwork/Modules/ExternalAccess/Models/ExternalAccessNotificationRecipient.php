<?php

namespace Artwork\Modules\ExternalAccess\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $recipient_type
 * @property int $recipient_id
 * @property array<int, string> $notification_types
 * @property int|null $created_by_user_id
 */
class ExternalAccessNotificationRecipient extends Model
{
    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'notification_types',
        'created_by_user_id',
    ];

    protected $casts = [
        'notification_types' => 'array',
    ];

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    public function listensFor(NotificationEnum $type): bool
    {
        return in_array($type->value, $this->notification_types ?? [], true);
    }
}
