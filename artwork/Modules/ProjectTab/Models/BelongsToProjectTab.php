<?php

namespace Artwork\Modules\ProjectTab\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToProjectTab
{
    public function projectTab(): BelongsTo
    {
        return $this->belongsTo(ProjectTab::class, 'tab_id', 'id', 'project_tab');
    }
}
