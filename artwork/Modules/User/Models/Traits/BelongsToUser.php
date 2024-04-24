<?php

namespace Artwork\Modules\User\Models\Traits;

use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'user');
    }
}
