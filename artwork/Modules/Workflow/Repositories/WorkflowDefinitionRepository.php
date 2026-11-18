<?php

namespace Artwork\Modules\Workflow\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Workflow\Models\WorkflowDefinition;

class WorkflowDefinitionRepository extends BaseRepository
{
    public function getModelClass(): string
    {
        return WorkflowDefinition::class;
    }

    public function create(array $data): WorkflowDefinition
    {
        return WorkflowDefinition::create($data);
    }

    public function getBuilder()
    {
        return WorkflowDefinition::query();
    }

    public function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->with('currentConfig')
            ->get();
    }
}
