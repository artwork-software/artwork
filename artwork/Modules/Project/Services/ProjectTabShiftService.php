<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Enums\ShiftTabSort;
use Artwork\Modules\Shift\Services\GlobalQualificationService;
use Artwork\Modules\Shift\Services\ShiftGroupService;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftUserService;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;

class ProjectTabShiftService
{
    public function __construct(
        private readonly ProjectTabService $projectTabService,
        private readonly ShiftQualificationService $shiftQualificationService,
        private readonly ProjectService $projectService,
        private readonly UserService $userService,
        private readonly FreelancerService $freelancerService,
        private readonly ServiceProviderService $serviceProviderService,
        private readonly CraftService $craftService,
        private readonly GlobalQualificationService $globalQualificationService,
        private readonly ShiftGroupService $shiftGroupService,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function buildShiftPayload(Project $project): array
    {
        $shiftTab = $this->projectTabService->getShiftTab(
            $project,
            $this->shiftQualificationService,
            $this->projectService,
            $this->userService,
            $this->freelancerService,
            $this->serviceProviderService,
            $this->craftService
        );

        return [
            'ShiftTab' => $shiftTab->toArray(),
            'shift_relevant_event_types' => $project->shiftRelevantEventTypes,
            'shift_tab_available_sortings' => array_map(
                fn (ShiftTabSort $sort) => $sort->value,
                ShiftTabSort::cases()
            ),
            'shift_contacts' => $project->shift_contact,
            'project_managers' => $project->managerUsers,
            'shiftDescription' => $project->shift_description,
            'freelancers' => Freelancer::all(),
            'serviceProviders' => ServiceProvider::without(['contacts'])->get(),
            // Ergänzt für ShiftPlanDailyView/AddShiftModal im Schichttab
            'globalQualifications' => $this->globalQualificationService->getAll(),
            'shiftGroups' => $this->shiftGroupService->getAllShiftGroups(),
        ];
    }
}

