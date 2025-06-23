<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectPrintLayout;
use Artwork\Modules\Project\Repositories\ProjectPrintLayoutRepository;
use Illuminate\Database\Eloquent\Collection;

readonly class ProjectPrintLayoutService
{

    public function __construct(private ProjectPrintLayoutRepository $projectPrintLayoutRepository)
    {
    }

    public function getAll(): Collection
    {
        return $this->projectPrintLayoutRepository->getAll();
    }

    public function getProjectPrintLayouts(): Collection
    {
        return $this->projectPrintLayoutRepository->getProjectPrintLayouts();
    }

    public function storeProjectPrintLayout(array $data): void
    {
        $data['order'] = $this->projectPrintLayoutRepository->getMaxOrder() + 1;
        $data['user_id'] = auth()->id();
        $this->projectPrintLayoutRepository->createProjectPrintLayout($data);
    }
}