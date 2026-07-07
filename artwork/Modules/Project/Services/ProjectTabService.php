<?php

namespace Artwork\Modules\Project\Services;

use Artwork\Core\Cache\ServiceWithArrayCache;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\Contract\Services\ContractTypeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerDropResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Project\Cache\ProjectTabArrayCache;
use Artwork\Modules\Project\DTOs\BudgetInformationDto;
use Artwork\Modules\Project\DTOs\ShiftsDto;
use Artwork\Modules\Project\Enum\ProjectTabComponentEnum;
use Artwork\Modules\Project\Models\ProjectTab;
use Artwork\Modules\Project\Repositories\ProjectTabRepository;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderDropResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\Shift\Enums\ShiftTabSort;
use Artwork\Modules\Shift\Services\ShiftQualificationService;
use Artwork\Modules\Shift\Services\ShiftTimePresetService;
use Artwork\Modules\User\Http\Resources\UserDropResource;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Services\UserService;
use Artwork\Modules\User\Services\WorkingHourService;
use Carbon\Carbon;

class ProjectTabService implements ServiceWithArrayCache
{
    public function __construct(
        private readonly ProjectTabRepository $projectTabRepository,
        private readonly ShiftTimePresetService $shiftTimePresetService,
        private readonly WorkingHourService $workingHourService
    ) {
    }


    private function authUser(): ?User
    {
        try {
            /** @var UserService $userService */
            $userService = app(UserService::class);
            return $userService->getAuthUser();
        } catch (\Throwable) {
            /** @var User|null $u */
            $u = auth()->user();
            return $u;
        }
    }

    public function findFirstProjectTab(): ProjectTab
    {
        $user = $this->authUser();
        $tab = $this->projectTabRepository->findFirstProjectTab($user);
        if (!$tab) {
            $tab = $this->projectTabRepository->findFirstProjectTab(null);
        }

        return $tab;
    }

    public function getDefaultOrFirstProjectTab(): ProjectTab
    {
        $user = $this->authUser();

        $tab = $this->projectTabRepository->getDefaultOrFirstProjectTab($user);

        if (!$tab) {
            $tab = $this->projectTabRepository->getDefaultOrFirstProjectTab(null);
        }

        return $tab;
    }

    public function getDefaultOrFirstProjectTabId(): int
    {
        return $this->getDefaultOrFirstProjectTab()->getAttribute('id');
    }

    public function getFirstProjectTabId(): int
    {
        return $this->findFirstProjectTab()->getAttribute('id');
    }

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::SHIFT_TAB);
    }

    private function findFirstProjectTabWithType(ProjectTabComponentEnum $type): ProjectTab|null
    {
        $user = $this->authUser();
        $uid  = $user?->id ?? 0;

        $cacheKey = $type->name . '|u' . $uid;

        if (!$projectTab = ProjectTabArrayCache::getItemByName($cacheKey)) {
            $projectTab = $this->projectTabRepository
                ->findFirstProjectTabByComponentsComponentType($type, $user);

            if ($projectTab) {
                ProjectTabArrayCache::setItem($projectTab);
            }
        }

        return $projectTab;
    }

    public function getFirstProjectTabWithTypeIdOrFirstProjectTabId(ProjectTabComponentEnum $type): int
    {
        return $this->findFirstProjectTabWithType($type)?->getAttribute('id') ??
            $this->getDefaultOrFirstProjectTabId();
    }

    public function getShiftTab(
        Project $project,
        ShiftQualificationService $shiftQualificationService,
        ProjectService $projectService,
        UserService $userService,
        FreelancerService $freelancerService,
        ServiceProviderService $serviceProviderService,
        CraftService $craftService
    ): ShiftsDto {
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addWeeks()->endOfDay();

        if (
            ($firstEventInProject = $projectService->getFirstEventInProject($project)) &&
            ($lastEventInProject = $projectService->getLastEventInProject($project))
        ) {
            $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
            $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
        }

        return ShiftsDto::newInstance()
            ->setUsersForShifts(
                $this->workingHourService->getUsersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    UserDropResource::class,
                    currentUser: $userService->getAuthUser()
                )
            )
            ->setFreelancersForShifts(
                $freelancerService->getFreelancersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    FreelancerDropResource::class,
                    currentUser: $userService->getAuthUser()
                )
            )
            ->setServiceProvidersForShifts(
                $serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    ServiceProviderDropResource::class,
                    currentUser: $userService->getAuthUser()
                )
            )
            ->setEventsWithRelevant($projectService->getEventsWithRelevantShifts($project))
            ->setCrafts($craftService->getAll(['users', 'freelancers', 'serviceProviders']))
            ->setCurrentUserCrafts($userService->getAuthUserCrafts()->merge($craftService->getAssignableByAllCrafts()))
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setShiftTimePresets($this->shiftTimePresetService->getAll())
            ->setShiftSortTypes(ShiftTabSort::cases());
    }

    public function getBudgetInformationDto(
        Project $project,
        ContractTypeService $contractTypeService,
        CompanyTypeService $companyTypeService,
        CurrencyService $currencyService
    ): BudgetInformationDto {
        return BudgetInformationDto::newInstance()
            ->setAccessBudget($project->access_budget)
            ->setContracts($project->contracts)
            ->setProjectFiles($project->project_files)
            ->setProjectMoneySources($project->moneySources)
            ->setProjectManagerIds($project->managerUsers->pluck('id'))
            ->setContractTypes($contractTypeService->getAll())
            ->setCompanyTypes($companyTypeService->getAll())
            ->setCurrencies($currencyService->getAll())
            ->setCostCenter($project->costCenter);
    }

    public function findByIdWithoutCache(int $id): ?Model
    {
        return $this->projectTabRepository->findById($id);
    }

    public function findByNameWithoutCache(string $name): ?Model
    {
        if (!$name) {
            return null;
        }
        return $this->projectTabRepository->findByName($name);
    }
}
