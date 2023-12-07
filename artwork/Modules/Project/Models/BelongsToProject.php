<?php

namespace Artwork\Modules\Project\Models;

use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToProject
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id', 'project');
    }
}
