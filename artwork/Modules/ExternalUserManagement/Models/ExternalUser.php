<?php

namespace Artwork\Modules\ExternalUserManagement\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $source_id
 * @property int|null $user_id
 * @property string $identification
 * @property array|null $meta_data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property ExternalUserSource $source
 * @property User|null $user
 */
class ExternalUser extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'source_id',
        'user_id',
        'identification',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(ExternalUserSource::class, 'source_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}

