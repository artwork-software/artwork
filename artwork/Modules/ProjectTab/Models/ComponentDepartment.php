<?php

namespace Artwork\Modules\ProjectTab\Models;

use App\Models\User;
use Artwork\Core\Database\Models\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentDepartment extends Pivot
{
    protected $fillable = [
        'department_id',
        'component_id',
        'can_write'
    ];

    protected $casts = [
        'can_write' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'component');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}
