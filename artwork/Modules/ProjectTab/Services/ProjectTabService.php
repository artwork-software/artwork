<?php

namespace Artwork\Modules\ProjectTab\Services;

use App\Enums\TabComponentEnums;
use App\Http\Controllers\CalendarController;
use App\Http\Resources\FreelancerDropResource;
use App\Http\Resources\ProjectCalendarShowEventResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Http\Resources\ServiceProviderDropResource;
use App\Http\Resources\UserDropResource;
use App\Models\Filter;
use App\Models\Freelancer;
use App\Models\ServiceProvider;
use App\Models\User;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\DTOs\CalendarDto;
use Artwork\Modules\ProjectTab\DTOs\ShiftsDto;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Repositories\ProjectTabRepository;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\ShiftQualification\Services\ShiftQualificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::SHIFT_TAB);
    }

    public function findFirstProjectTabWithTasksComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::CHECKLIST);
    }

    public function findFirstProjectTabWithBudgetComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::BUDGET);
    }

    public function findFirstProjectTabWithCalendarComponent(): ProjectTab|null
    {
        return $this->projectTabRepository->findFirstProjectTabByComponentsComponentType(TabComponentEnums::CALENDAR);
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
        return $this->createCalendarDto(
            $calendarData,
            $eventsAtAGlance,
            $roomService->filterRooms($startDate, $endDate)->get(),
            new CalendarEventCollectionResourceModel(
                $calendarData['filterOptions']['areas'],
                $calendarData['filterOptions']['projects'],
                $calendarData['filterOptions']['eventTypes'],
                $calendarData['filterOptions']['roomCategories'],
                $calendarData['filterOptions']['roomAttributes'],
                $calendarController->getEventsOfInterval($startDate, $endDate, $project),
                Filter::query()->where('user_id', Auth::id())->get(),
            )
        );
    }

    public function createCalendarDto(
        array $calendarData,
        Collection $eventsAtAGlance,
        EloquentCollection $filteredRooms,
        CalendarEventCollectionResourceModel $calendarEventCollectionResourceModel
    ): CalendarDto {
        $calendarDto = new CalendarDto();

        $calendarDto->setCalendar($calendarData['roomsWithEvents']);
        $calendarDto->setDateValue($calendarData['dateValue']);
        $calendarDto->setDays($calendarData['days']);
        $calendarDto->setSelectedDate($calendarData['selectedDate']);
        $calendarDto->setFilterOptions($calendarData["filterOptions"]);
        $calendarDto->setPersonalFilters($calendarData['personalFilters']);
        $calendarDto->setEventsWithoutRoom($calendarData['eventsWithoutRoom']);
        $calendarDto->setUserFilters($calendarData['user_filters']);
        $calendarDto->setEventsAtAGlance($eventsAtAGlance);
        $calendarDto->setRooms($filteredRooms);
        $calendarDto->setEvents($calendarEventCollectionResourceModel);

        return $calendarDto;
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

        return $this->createShiftsDto(
            $usersWithPlannedWorkingHours,
            $freelancersWithPlannedWorkingHours,
            $serviceProvidersWithPlannedWorkingHours,
            $eventsWithRelevant,
            Craft::all(),
            Auth::user()->crafts->merge(Craft::query()->where('assignable_by_all', '=', true)->get()),
            $shiftQualificationService->getAllOrderedByCreationDateAscending()
        );
    }

    public function createShiftsDto(
        array $usersWithPlannedWorkingHours,
        array $freelancersWithPlannedWorkingHours,
        array $serviceProvidersWithPlannedWorkingHours,
        array $eventsWithRelevant,
        EloquentCollection $crafts,
        EloquentCollection $currentUserCrafts,
        EloquentCollection $shiftQualifications
    ): ShiftsDto {
        $shiftsDto = new ShiftsDto();

        $shiftsDto->setUsersForShifts($usersWithPlannedWorkingHours);
        $shiftsDto->setFreelancersForShifts($freelancersWithPlannedWorkingHours);
        $shiftsDto->setServiceProvidersForShifts($serviceProvidersWithPlannedWorkingHours);
        $shiftsDto->setEventsWithRelevant($eventsWithRelevant);
        $shiftsDto->setCrafts($crafts);
        $shiftsDto->setCurrentUserCrafts($currentUserCrafts);
        $shiftsDto->setShiftQualifications($shiftQualifications);

        return $shiftsDto;
    }
}
