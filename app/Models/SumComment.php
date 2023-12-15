<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $commentable_type
 * @property string $commentable_id
 * @property string $comment
 * @property int $user_id
 * @property string $created_at
 * @property string $updated_at
 */
class SumComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
