<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Repositories\ProjectStateRepository;
use Illuminate\Support\Collection;


class ProjectStateService
{
    public function __construct(
        private readonly ProjectStateRepository $projectStateRepository
    ) {
    }

    public function getAll(): Collection
    {
        return $this->projectStateRepository->getAll();
    }
}
