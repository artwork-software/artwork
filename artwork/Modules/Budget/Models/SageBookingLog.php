<?php

namespace Artwork\Modules\Budget\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $type
 * @property string|null $source
 * @property int|null $user_id
 * @property array|null $criteria
 * @property int $created_count
 * @property int $updated_count
 * @property int $deleted_count
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 */
class SageBookingLog extends Model
{
    use Prunable;

    protected $fillable = [
        'type',
        'source',
        'user_id',
        'criteria',
        'created_count',
        'updated_count',
        'deleted_count',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'criteria' => 'array',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'deleted_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(SageBookingLogEntry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(360));
    }
}
