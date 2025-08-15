<?php

namespace Artwork\Modules\Workflow\Contracts;

use Artwork\Modules\Workflow\Models\WorkflowInstance;

interface WorkflowAction
{
    /**
     * Execute the action
     */
    public function execute(WorkflowInstance $workflowInstance, array $parameters = []): void;

    /**
     * Check if the action can be executed
     */
    public function canExecute(WorkflowInstance $workflowInstance, array $parameters = []): bool;

    /**
     * Get the action name
     */
    public function getName(): string;
}