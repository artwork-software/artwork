<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectGroupStoreRequest;
use Artwork\Modules\Project\Repositories\ProjectRepository;

class ProjectGroupService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    )
    {
    }

    public function storeByRequest(ProjectGroupStoreRequest $projectStoreRequest): void
    {

    }
}
