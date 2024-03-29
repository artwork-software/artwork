<?php

namespace Artwork\Modules\Notification\Models;

use App\Enums\NotificationConstEnum;
use App\Enums\NotificationFrequency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $group_type
 * @property string $type
 * @property string $title
 * @property string $description
 * @property string $frequency
 * @property int $enabled_email
 * @property int $enabled_push
 * @property string $created_at
 * @property string $updated_at
 */
class NotificationSetting extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ["frequency_title"];

    protected $casts = [
        'frequency' => NotificationFrequency::class,
        'type' => NotificationConstEnum::class,
        'enabled_email' => 'boolean',
        'enabled_push' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFrequencyTitleAttribute(): string
    {
        return $this->frequency->title();
    }
}
