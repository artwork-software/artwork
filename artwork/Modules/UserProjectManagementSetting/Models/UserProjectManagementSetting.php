<?php

namespace Artwork\Modules\UserProjectManagementSetting\Models;

use Artwork\Core\Database\Models\Model;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProjectManagementSetting extends Model
{
    protected $fillable = [
        'user_id',
        'settings'
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }
}