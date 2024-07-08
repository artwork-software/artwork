<?php

namespace Artwork\Modules\ChecklistTemplate\Services;

use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;
use Artwork\Modules\ChecklistTemplate\Repositories\ChecklistTemplateRepository;

readonly class ChecklistTemplateService
{
    public function __construct(
        private ChecklistTemplateRepository $checklistTemplateRepository
    ) {
    }

    public function createNewChecklistTemplate(array $attributes): ChecklistTemplate
    {
        return new ChecklistTemplate($attributes);
    }

    public function duplicate(ChecklistTemplate $checklistTemplate, int $userId): ChecklistTemplate
    {
        $newChecklistTemplate = $this->createNewChecklistTemplate([
            'name' => $checklistTemplate->name . ' (Kopie)',
            'user_id' => $userId,
        ]);
        $this->checklistTemplateRepository->save($newChecklistTemplate);
        $newChecklistTemplate->users()->sync($checklistTemplate->users->pluck('id'));
        return $newChecklistTemplate;
    }
}
