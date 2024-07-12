<?php

namespace Artwork\Modules\ChecklistTemplate\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\ChecklistTemplate\Models\ChecklistTemplate;

class ChecklistTemplateRepository extends BaseRepository
{
    /**
     * @return array<string, mixed>
     */
    public function syncUsers(ChecklistTemplate $checklistTemplate, array $userIds): array
    {
        return $checklistTemplate->users()->sync($userIds);
    }
}
