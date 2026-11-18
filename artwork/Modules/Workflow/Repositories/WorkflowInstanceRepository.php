<?php

namespace Artwork\Modules\Workflow\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Workflow\Models\WorkflowInstance;

class WorkflowInstanceRepository extends BaseRepository
{
    public function getModelClass(): string
    {
        return WorkflowInstance::class;
    }

    public function create(array $data): WorkflowInstance
    {
        return WorkflowInstance::create($data);
    }

    public function getBuilder()
    {
        return WorkflowInstance::query();
    }
}
