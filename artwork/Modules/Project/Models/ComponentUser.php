<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Core\Database\Models\Pivot;
use Artwork\Modules\Project\Models\Component;
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}
