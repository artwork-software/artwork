<?php

namespace Artwork\Modules\TaskTemplate\Services;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Artwork\Modules\TaskTemplate\Repositories\TaskTemplateRepository;

readonly class TaskTemplateService
{
    public function __construct(private TaskTemplateRepository $taskTemplateRepository)
    {
    }

    public function duplicateTaskTemplates(
        ChecklistTemplate $checklistTemplate,
        ChecklistTemplate $newChecklistTemplate
    ): void {
        /** @var TaskTemplate $taskTemplate */
        foreach ($checklistTemplate->getAttribute('task_templates') as $taskTemplate) {
            /** @var TaskTemplate $newTaskTemplate */
            $newTaskTemplate = $this->taskTemplateRepository->replicate($taskTemplate);
            $this->taskTemplateRepository->save($newTaskTemplate);

            /** @var TaskTemplate $newTaskTemplate */
            $newTaskTemplate = $this->taskTemplateRepository->update(
                $newTaskTemplate,
                [
                    'checklist_template_id' => $newChecklistTemplate->getAttribute('id')
                ]
            );

            $this->taskTemplateRepository->syncTaskUsers(
                $newTaskTemplate,
                $taskTemplate->getAttribute('task_users')->pluck('id')
            );
        }
    }
}
