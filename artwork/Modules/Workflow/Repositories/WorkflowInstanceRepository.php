<?php

namespace Artwork\Modules\Workflow\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;

class WorkflowInstanceRepository extends BaseRepository
{
    public function getModelClass(): string
    {
        return WorkflowInstance::class;
    }

    public function findBySubject(Model $subject): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->with(['workflowDefinitionConfig.workflowDefinition', 'currentData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findActiveBySubject(Model $subject): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->whereNull('completed_at')
            ->with(['workflowDefinitionConfig.workflowDefinition', 'currentData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findByDefinitionAndSubject(
        int $definitionId,
        Model $subject
    ): \Illuminate\Database\Eloquent\Collection {
        return $this->getBuilder()
            ->whereHas('workflowDefinitionConfig', function ($query) use ($definitionId) {
                $query->where('workflow_definition_id', $definitionId);
            })
            ->where('subject_type', get_class($subject))
            ->where('subject_id', $subject->id)
            ->with(['workflowDefinitionConfig', 'currentData'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRunning(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->whereNull('completed_at')
            ->with(['workflowDefinitionConfig.workflowDefinition', 'subject'])
            ->get();
    }
}
