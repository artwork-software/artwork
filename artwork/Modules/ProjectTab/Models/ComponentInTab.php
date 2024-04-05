<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComponentInTab extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_id',
        'component_id',
        'order',
        'data'
    ];

    protected $with = ['component'];

    public function component(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}
