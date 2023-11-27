<?php

namespace Artwork\Modules\Budget\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SumComment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    //@todo belongs to user trait
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
