<?php

namespace Artwork\Modules\Project\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToProject
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }
}
