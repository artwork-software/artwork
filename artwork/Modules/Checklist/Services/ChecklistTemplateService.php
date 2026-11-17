<?php

namespace Artwork\Modules\Checklist\Services;

use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Artwork\Modules\Checklist\Repositories\ChecklistTemplateRepository;

readonly class ChecklistTemplateService
{
    public function __construct(
        private ChecklistTemplateRepository $checklistTemplateRepository
    ) {
    }

    public function createChecklistTemplate(array $attributes): ChecklistTemplate
    {
        return new ChecklistTemplate($attributes);
    }

    public function duplicate(ChecklistTemplate $checklistTemplate, int $userId): ChecklistTemplate
    {
        // Namen kürzen, damit der Zusatz nicht über die Spaltenlänge (255) hinausläuft
        $suffix = ' ' . __('(Copy)');
        $name = mb_substr((string) $checklistTemplate->getAttribute('name'), 0, 255 - mb_strlen($suffix)) . $suffix;

        $newChecklistTemplate = $this->createChecklistTemplate([
            'name' => $name,
            'user_id' => $userId,
        ]);
        $this->checklistTemplateRepository->save($newChecklistTemplate);

        $this->checklistTemplateRepository->syncUsers(
            $newChecklistTemplate,
            $checklistTemplate->getAttribute('users')->pluck('id')
        );

        return $newChecklistTemplate;
    }
}
