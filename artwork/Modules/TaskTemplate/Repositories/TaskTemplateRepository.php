<?php

namespace Artwork\Modules\TaskTemplate\Repositories;

use Artwork\Core\Database\Repository\BaseRepository;
use Artwork\Modules\TaskTemplate\Models\TaskTemplate;
use Illuminate\Support\Collection;

class TaskTemplateRepository extends BaseRepository
{
    /**
     * @return array<string, mixed>
     */
    public function syncTaskUsers(TaskTemplate $taskTemplate, Collection $userIds): array
    {
        return $taskTemplate->task_users()->sync($userIds);
    }
}
