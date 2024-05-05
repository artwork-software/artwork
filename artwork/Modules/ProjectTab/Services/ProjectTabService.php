<?php

namespace Artwork\Modules\ProjectTab\Services;

use App\Http\Controllers\CalendarController;
use Artwork\Modules\CollectingSociety\Services\CollectingSocietyService;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\ContractType\Services\ContractTypeService;
use Artwork\Modules\Craft\Services\CraftService;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Freelancer\Http\Resources\FreelancerDropResource;
use Artwork\Modules\Freelancer\Services\FreelancerService;
use Artwork\Modules\Project\Http\Resources\ProjectCalendarShowEventResource;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\ProjectTab\DTOs\BudgetInformationDto;
use Artwork\Modules\ProjectTab\DTOs\CalendarDto;
use Artwork\Modules\ProjectTab\DTOs\ShiftsDto;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Repositories\ProjectTabRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\Http\Resources\ServiceProviderDropResource;
use Artwork\Modules\ServiceProvider\Services\ServiceProviderService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\Http\Resources\UserDropResource;
use Artwork\Modules\User\Services\UserService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

readonly class ProjectTabService
{
    public function __construct(private ProjectTabRepository $projectTabRepository)
    {
    }

    public function findFirstProjectTab(): ProjectTab|null
    {
        return $this->projectTabRepository
            ->findFirstProjectTab();
    }

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        return $this->projectTabRepository
            ->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::SHIFT_TAB);
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        return $this->projectTabRepository
            ->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::CHECKLIST);
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        return $this->projectTabRepository
            ->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::BUDGET);
    }

    public function findFirstProjectTabWithCalendarComponent(): ProjectTab|null
    {
        return $this->projectTabRepository
            ->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::CALENDAR);
    }

    public function getCalendarTab(
        Project $project,
        RoomService $roomService,
        CalendarController $calendarController
    ): CalendarDto {
        if (\request('startDate') && \request('endDate')) {
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        $calendarData = $calendarController->createCalendarData(
            project: $project,
            user: Auth::user()
        );
        $eventsAtAGlance = Collection::make();
        if (\request('atAGlance') === 'true') {
            $eventsAtAGlance = ProjectCalendarShowEventResource::collection(
                $calendarController
                    ->filterEvents($project->events(), null, null, null, $project)
                    ->with(['room','project','creator'])
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
            ->setRooms($roomService->filterRooms($startDate, $endDate)->get())
            ->setEvents(
                CalendarEventDto::newInstance()
                    ->setAreas($calendarData['filterOptions']['areas'])
                    ->setProjects($calendarData['filterOptions']['projects'])
                    ->setEventTypes($calendarData['filterOptions']['eventTypes'])
                    ->setRoomCategories($calendarData['filterOptions']['roomCategories'])
                    ->setRoomAttributes($calendarData['filterOptions']['roomAttributes'])
                    ->setEvents($calendarController->getEventsOfInterval($startDate, $endDate, $project))
                    ->setFilter(Filter::query()->where('user_id', Auth::id())->get())
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
            ->setUsersForShifts($userService->getUsersWithPlannedWorkingHours(
                $startDate,
                $endDate,
                UserDropResource::class
            ))
            ->setFreelancersForShifts($freelancerService->getFreelancersWithPlannedWorkingHours(
                $startDate,
                $endDate,
                FreelancerDropResource::class
            ))
            ->setServiceProvidersForShifts($serviceProviderService->getServiceProvidersWithPlannedWorkingHours(
                $startDate,
                $endDate,
                ServiceProviderDropResource::class
            ))
            ->setEventsWithRelevant($projectService->getEventsWithRelevantShifts($project))
            ->setCrafts($craftService->getAll())
            ->setCurrentUserCrafts($userService->getAuthUserCrafts()->merge($craftService->getAssignableByAllCrafts()))
            ->setShiftQualifications($shiftQualificationService->getAllOrderedByCreationDateAscending());
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
            ->setProjectManagerIds($project->managerUsers->pluck('user_id'))
            ->setContractTypes($contractTypeService->getAll())
            ->setCompanyTypes($companyTypeService->getAll())
            ->setCurrencies($currencyService->getAll())
            ->setCollectingSocieties($collectingSocietyService->getAll())
            ->setCostCenter($project->costCenter);
    }
}
