<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;

class SidebarTabComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_sidebar_id',
        'component_id',
        'order',
    ];

    protected $with = ['component'];

    public function component(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}
