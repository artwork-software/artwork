<?php

namespace Artwork\Modules\ProjectTab\Services;

use App\Http\Controllers\CalendarController;
use App\Http\Resources\FreelancerDropResource;
use App\Http\Resources\ProjectCalendarShowEventResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Http\Resources\ServiceProviderDropResource;
use App\Http\Resources\UserDropResource;
use Artwork\Modules\CollectingSociety\Services\CollectingSocietyService;
use Artwork\Modules\CompanyType\Services\CompanyTypeService;
use Artwork\Modules\ContractType\Services\ContractTypeService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Currency\Services\CurrencyService;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\DTOs\BudgetInformationDto;
use Artwork\Modules\ProjectTab\DTOs\CalendarDto;
use Artwork\Modules\ProjectTab\DTOs\ShiftsDto;
use Artwork\Modules\ProjectTab\Enums\ProjectTabComponentEnum;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Repositories\ProjectTabRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Artwork\Modules\User\Models\User;
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
        return $this->projectTabRepository->findFirstProjectTab();
    }

    public function findFirstProjectTabWithShiftsComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::SHIFT_TAB);
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::CHECKLIST);
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::BUDGET);
    }

    public function findFirstProjectTabWithCalendarComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(ProjectTabComponentEnum::CALENDAR);
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
            ->setEvents(new CalendarEventCollectionResourceModel(
                $calendarData['filterOptions']['areas'],
                $calendarData['filterOptions']['projects'],
                $calendarData['filterOptions']['eventTypes'],
                $calendarData['filterOptions']['roomCategories'],
                $calendarData['filterOptions']['roomAttributes'],
                $calendarController->getEventsOfInterval($startDate, $endDate, $project),
                Filter::query()->where('user_id', Auth::id())->get(),
            ));
    }

    public function getShiftTab(
        Project $project,
        ShiftQualificationService $shiftQualificationService
    ): ShiftsDto {
        $shiftRelevantEventTypes = $project->shiftRelevantEventTypes()->pluck('event_type_id');
        $shiftRelevantEvents = $project->events()
            ->whereIn('event_type_id', $shiftRelevantEventTypes)
            ->with(['timelines', 'shifts', 'event_type', 'room'])
            ->get();

        $eventsWithRelevant = [];
        foreach ($shiftRelevantEvents as $event) {
            $timeline = $event->timelines()->get()->toArray();

            foreach ($timeline as &$singleTimeLine) {
                $singleTimeLine['description_without_html'] = strip_tags($singleTimeLine['description']);
            }

            usort($timeline, function ($a, $b) {
                if ($a['start'] === null && $b['start'] === null) {
                    return 0;
                } elseif ($a['start'] === null) {
                    return 1; // $a should come later in the array
                } elseif ($b['start'] === null) {
                    return -1; // $b should come later in the array
                }

                // Compare the 'start' values for ascending order
                return strtotime($a['start']) - strtotime($b['start']);
            });


            foreach ($event->shifts as $shift) {
                $shift->load('shiftsQualifications');
            }

            $eventsWithRelevant[$event->id] = [
                'event' => $event,
                'timeline' => $timeline,
                'shifts' => $event->shifts,
                'event_type' => $event->event_type,
                'room' => $event->room,
            ];
        }
        rsort($eventsWithRelevant);

        $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->limit(1)->first();
        $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->limit(1)->first();
        if ($firstEventInProject && $lastEventInProject) {
            //get the start of day of the firstEventInProject
            $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
            //get the end of day of the lastEventInProject
            $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        //get the diff of startDate and endDate in days, +1 to include the current date
        $diffInDays = $startDate->diffInDays($endDate) + 1;

        $usersWithPlannedWorkingHours = [];
        foreach (User::query()->where('can_work_shifts', true)->get() as $user) {
            $usersWithPlannedWorkingHours[] = [
                'user' => UserDropResource::make($user),
                'plannedWorkingHours' => $user->plannedWorkingHours($startDate, $endDate),
                'expectedWorkingHours' => ($user->weekly_working_hours / 7) * $diffInDays,
                'vacations' => $user->hasVacationDays(),
            ];
        }

        $freelancersWithPlannedWorkingHours = [];
        foreach (Freelancer::query()->where('can_work_shifts', true)->get() as $freelancer) {
            $freelancersWithPlannedWorkingHours[] = [
                'freelancer' => FreelancerDropResource::make($freelancer),
                'plannedWorkingHours' => $freelancer->plannedWorkingHours($startDate, $endDate),
            ];
        }

        $serviceProvidersWithPlannedWorkingHours = [];
        foreach (
            ServiceProvider::query()
                ->where('can_work_shifts', true)
                ->without(['contacts'])
                ->get() as $service_provider
        ) {
            $serviceProvidersWithPlannedWorkingHours[] = [
                'service_provider' => ServiceProviderDropResource::make($service_provider),
                'plannedWorkingHours' => $service_provider->plannedWorkingHours($startDate, $endDate),
            ];
        }

        return ShiftsDto::newInstance()
            ->setUsersForShifts($usersWithPlannedWorkingHours)
            ->setFreelancersForShifts($freelancersWithPlannedWorkingHours)
            ->setServiceProvidersForShifts($serviceProvidersWithPlannedWorkingHours)
            ->setEventsWithRelevant($eventsWithRelevant)
            ->setCrafts(Craft::all())
            ->setCurrentUserCrafts(
                Auth::user()
                    ->crafts
                    ->merge(Craft::query()->where('assignable_by_all', '=', true)->get())
            )
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
            ->setCollectingSocieties($collectingSocietyService->getAll());
    }
}
