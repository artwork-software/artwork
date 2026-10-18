<?php

namespace Artwork\Modules\Project\Models;

use Artwork\Modules\Project\Models\Component;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Artwork\Core\Database\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SidebarTabComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_tab_sidebar_id',
        'component_id',
        'order',
    ];

    protected $with = ['component'];

    /**
     * @return BelongsTo<Component, $this>
     */
    public function component(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Component::class, 'component_id', 'id', 'component');
    }
}
