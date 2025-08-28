<?php

namespace Artwork\Modules\Workflow\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface WorkflowSubject
{
    public function workflowInstances(): MorphMany;
    
    public function getWorkflowSubjectInfo(): array;
    
    public function canHaveWorkflow(string $workflowType): bool;
}
