<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\ProjectManagementBuilder;
use Artwork\Modules\Project\Repositories\ProjectManagementBuilderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

readonly class ProjectManagementBuilderService
{

    public function __construct(private ProjectManagementBuilderRepository $projectManagementBuilderRepository)
    {
    }

    public function getProjectManagementBuilder(array $with = []): Collection
    {
        return $this->projectManagementBuilderRepository->getProjectManagementBuilder($with);
    }

    public function updateOrder(SupportCollection $components): void
    {
        foreach ($components as $component) {
            ProjectManagementBuilder::where('id', $component['id'])->update(['order' => $component['order']]);
        }
    }

    public function reorderComponents(): void
    {
        $components = ProjectManagementBuilder::orderBy('order')->get();
        $order = 1;
        foreach ($components as $component) {
            ProjectManagementBuilder::where('id', $component->id)->update(['order' => $order]);
            $order++;
        }
    }

    // Add service logic here
}