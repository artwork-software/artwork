<?php

namespace Artwork\Modules\GlobalNotification\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $image_name
 * @property int $created_by
 * @property string $expiration_date
 * @property string $created_at
 * @property string $updated_at
 */
class GlobalNotification extends Model
{
    use HasFactory;

    protected $fillable = [
      'title',
      'description',
      'image_name',
      'expiration_date',
      'created_by'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id',
            'created_by',
            'global_notifications'
        );
    }
}
