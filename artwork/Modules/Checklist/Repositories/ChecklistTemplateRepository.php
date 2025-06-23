<?php

namespace Artwork\Modules\Checklist\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\Checklist\Models\ChecklistTemplate;
use Illuminate\Support\Collection;

class ChecklistTemplateRepository extends BaseRepository
{
    /**
     * @return array<string, mixed>
     */
    public function syncUsers(ChecklistTemplate $checklistTemplate, Collection $userIds): array
    {
        return $checklistTemplate->users()->sync($userIds);
    }
}
