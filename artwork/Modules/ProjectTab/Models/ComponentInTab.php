<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentInTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_id',
        'component_id',
        'order',
        'scope'
    ];

    protected $with = ['component'];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}
