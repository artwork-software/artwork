<?php

namespace Artwork\Modules\TaskTemplate\Services;

use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
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
        $checklistTemplate->task_templates->each(function ($taskTemplate) use ($newChecklistTemplate): void {
            $newTaskTemplate = $taskTemplate->replicate();
            $newTaskTemplate->checklist_template_id = $newChecklistTemplate->id;
            $newTaskTemplate->task_users()->sync($taskTemplate->task_users->pluck('id'));
            $this->taskTemplateRepository->save($newTaskTemplate);
        });
    }
}
