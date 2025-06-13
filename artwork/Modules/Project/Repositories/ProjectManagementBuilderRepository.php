<?php

namespace Artwork\Modules\Project\Repositories;

use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Illuminate\Database\Eloquent\Collection;

readonly class ProjectManagementBuilderRepository
{
    // Add repository logic here

    public function getProjectManagementBuilder(array $with = []): Collection
    {
        return ProjectManagementBuilder::with($with)->orderBy('order')->get();
    }
}