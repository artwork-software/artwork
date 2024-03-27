<?php

namespace App\Models;

use Antonrom\ModelChangesHistory\Traits\HasChangesHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $from
 * @property string $until
 * @property string $created_at
 * @property string $updated_at
 */
class UserVacations extends Model
{
    use HasFactory;
    use HasChangesHistory;

    protected $fillable = [
        'user_id',
        'from',
        'until'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
