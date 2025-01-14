<?php

namespace Artwork\Modules\ProjectManagementBuilder\Repositories;

use Artwork\Modules\ProjectManagementBuilder\Models\ProjectManagementBuilder;
use Illuminate\Database\Eloquent\Collection;

readonly class ProjectManagementBuilderRepository
{
    // Add repository logic here

    public function getProjectManagementBuilder(): Collection
    {
        return ProjectManagementBuilder::orderBy('order')->get();
    }
}