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

    public function findByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('type', $type)
            ->where('is_active', true)
            ->get();
    }

    public function findActiveByName(string $name): ?WorkflowDefinition
    {
        return $this->getBuilder()
            ->where('name', $name)
            ->where('is_active', true)
            ->first();
    }

    public function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('is_active', true)
            ->with('currentConfig')
            ->get();
    }
}
