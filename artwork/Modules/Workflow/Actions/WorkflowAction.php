<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Workflow\Models\WorkflowInstance;

interface WorkflowAction
{
    public function execute(WorkflowInstance $workflowInstance, array $parameters = []): void;

    public function canExecute(WorkflowInstance $workflowInstance, array $parameters = []): bool;

    public function getName(): string;
}
