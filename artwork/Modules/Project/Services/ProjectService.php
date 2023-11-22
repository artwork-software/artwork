<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Http\Requests\ProjectStoreRequest;
use Artwork\Modules\Project\Repositories\ProjectFileRepository;
use Artwork\Modules\Project\Repositories\ProjectHeadlineRepository;
use Artwork\Modules\Project\Repositories\ProjectRepository;
use Artwork\Modules\Project\Repositories\ProjectStateRepository;

class ProjectService
{


    public function __construct(
        private readonly ProjectFileRepository $projectFileRepository,
        private readonly  ProjectHeadlineRepository $projectHeadlineRepository,
        private readonly  ProjectStateRepository $projectStateRepository,
        private readonly  ProjectRepository $projectRepository,
    ){}


    public function storeByRequest(ProjectStoreRequest $projectStoreRequest): void
    {

    }
}
