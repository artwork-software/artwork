<?php

namespace Artwork\Modules\ProjectTab\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentUser extends Pivot
{
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'component_id',
        'can_write'
    ];

    protected $casts = [
        'can_write' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
