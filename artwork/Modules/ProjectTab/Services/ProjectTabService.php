<?php

namespace Artwork\Modules\ProjectTab\Services;

use App\Http\Controllers\FilterController;
use Artwork\Core\Cache\ServiceWithArrayCache;
use Artwork\Core\Database\Models\Model;
use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\CollectingSociety\Services\CollectingSocietyService;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\ContractType\Services\ContractTypeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerDropResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Project\Http\Resources\ProjectCalendarShowEventResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\Cache\ProjectTabArrayCache;
use Artwork\Modules\ProjectTab\DTOs\BudgetInformationDto;
use Artwork\Modules\ProjectTab\DTOs\CalendarDto;
use Artwork\Modules\ProjectTab\DTOs\ShiftsDto;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Repositories\ProjectTabRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderDropResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\ShiftTimePreset\Services\ShiftTimePresetService;
use Artwork\Modules\User\Http\Resources\UserDropResource;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

readonly class ProjectTabService implements ServiceWithArrayCache
{
    public function __construct(
        private ProjectTabRepository $projectTabRepository,
        private readonly ShiftTimePresetService $shiftTimePresetService
    ) {
    }

    public function findFirstProjectTab(): ProjectTab|null
    {
        return $this->projectTabRepository
            ->findFirstProjectTab();
    }

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::SHIFT_TAB);
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::CHECKLIST);
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::BUDGET);
    }

    public function findFirstProjectTabWithCalendarComponent(): ProjectTab|null
    {
        return $this->findFirstProjectTabWithType(ProjectTabComponentEnum::CALENDAR);
    }

    private function findFirstProjectTabWithType(ProjectTabComponentEnum $type): ProjectTab|null
    {
        if (!$projectTab = ProjectTabArrayCache::getItemByName($type->name)) {
            $projectTab = $this->projectTabRepository
                ->findFirstProjectTabByComponentsComponentType($type);
            ProjectTabArrayCache::setItem($projectTab);
        }
        return $projectTab;
    }

    public function getCalendarTab(
        Carbon $startDate,
        Carbon $endDate,
        Project $project,
        RoomService $roomService,
        CalendarService $calendarService,
        ProjectService $projectService,
        UserService $userService,
        FilterService $filterService,
        FilterController $filterController,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        bool $atAGlance
    ): CalendarDto {
        if (
            ($firstEventInProject = $projectService->getFirstEventInProject($project)) &&
            ($lastEventInProject = $projectService->getLastEventInProject($project))
        ) {
            $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
            $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
        }

        $calendarData = $calendarService->createCalendarData(
            $startDate,
            $endDate,
            $userService,
            $filterService,
            $filterController,
            $roomService,
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService,
            Auth::user()->getCalendarFilter(),
        );

        $eventsAtAGlance = Collection::make();
        if ($atAGlance) {
            $eventsAtAGlance = ProjectCalendarShowEventResource::collection(
                $calendarService
                    ->filterEvents($project->events(), null, null, null, $project)
                    ->with(['room', 'project', 'creator'])
                    ->orderBy('start_time', 'ASC')
                    ->get()
            )->collection->groupBy('room.id');
        }

        return CalendarDto::newInstance()
            ->setCalendar($calendarData['roomsWithEvents'])
            ->setDateValue($calendarData['dateValue'])
            ->setDays($calendarData['days'])
            ->setSelectedDate($calendarData['selectedDate'])
            ->setFilterOptions($calendarData["filterOptions"])
            ->setPersonalFilters($calendarData['personalFilters'])
            ->setEventsWithoutRoom($calendarData['eventsWithoutRoom'])
            ->setUserFilters($calendarData['user_filters'])
            ->setEventsAtAGlance($eventsAtAGlance)
            ->setRooms(
                $roomService->getFilteredRooms(
                    $startDate,
                    $endDate,
                    $userService->getAuthUser()->calendar_filter
                )
            )
            ->setEvents(
                CalendarEventDto::newInstance()
                    ->setAreas($calendarData['filterOptions']['areas'])
                    ->setProjects($calendarData['filterOptions']['projects'])
                    ->setEventTypes($calendarData['filterOptions']['eventTypes'])
                    ->setRoomCategories($calendarData['filterOptions']['roomCategories'])
                    ->setRoomAttributes($calendarData['filterOptions']['roomAttributes'])
                    ->setEvents($calendarService->getEventsOfInterval($startDate, $endDate, $project))
            );
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
            //get the start of day of the firstEventInProject
            $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
            //get the end of day of the lastEventInProject
            $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
        }

        return ShiftsDto::newInstance()
            ->setUsersForShifts(
                $userService->getUsersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    UserDropResource::class
                )
            )
            ->setFreelancersForShifts(
                $freelancerService->getFreelancersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    FreelancerDropResource::class
                )
            )
            ->setServiceProvidersForShifts(
                $serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
                    $startDate,
                    $endDate,
                    ServiceProviderDropResource::class
                )
            )
            ->setEventsWithRelevant($projectService->getEventsWithRelevantShifts($project))
            ->setCrafts($craftService->getAll())
            ->setCurrentUserCrafts($userService->getAuthUserCrafts()->merge($craftService->getAssignableByAllCrafts()))
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending())
            ->setShiftTimePresets($this->shiftTimePresetService->getAll());
    }

    public function getBudgetInformationDto(
        Project $project,
        ContractTypeService $contractTypeService,
        CompanyTypeService $companyTypeService,
        CurrencyService $currencyService,
        CollectingSocietyService $collectingSocietyService
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
            ->setCollectingSocieties($collectingSocietyService->getAll())
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
