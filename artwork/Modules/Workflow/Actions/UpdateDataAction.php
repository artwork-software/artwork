<?php

namespace Artwork\Modules\Workflow\Actions;

use Artwork\Modules\Workflow\Models\WorkflowInstance;

class UpdateDataAction implements WorkflowAction
{
    public function execute(WorkflowInstance $workflowInstance, array $parameters = []): void
    {
        $dataToUpdate = $parameters['data'] ?? [];

        if (empty($dataToUpdate)) {
            return;
        }

        $currentData = $workflowInstance->getData();
        $newData = array_merge($currentData, $dataToUpdate);

        $workflowInstance->saveData($newData);
    }

    public function canExecute(WorkflowInstance $workflowInstance, array $parameters = []): bool
    {
        return !empty($parameters['data']) && is_array($parameters['data']);
    }

    public function getName(): string
    {
        return 'update_data';
    }
}
