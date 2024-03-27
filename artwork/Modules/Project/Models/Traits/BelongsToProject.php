<?php

namespace Artwork\Modules\Project\Models\Traits;

use Artwork\Modules\Project\Models\Project;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToProject
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }
}
