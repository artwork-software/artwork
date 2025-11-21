<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Project\Models\Project;

class ProjectTabShiftContactsService
{
    public function buildShiftContactsPayload(Project $project): array
    {
        return [
            'shift_contacts' => $project->shift_contact,
            'project_managers' => $project->managerUsers,
        ];
    }
}

