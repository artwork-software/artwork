<?php

namespace Artwork\Modules\ProjectTab\Services;

use App\Enums\TabComponentEnums;
use App\Http\Controllers\CalendarController;
use App\Http\Resources\ProjectCalendarShowEventResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Models\Filter;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\ProjectTab\DTOs\CalendarDto;
use Artwork\Modules\ProjectTab\Models\ProjectTab;
use Artwork\Modules\ProjectTab\Repositories\ProjectTabRepository;
use Artwork\Modules\Room\Services\RoomService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProjectTabService
{
    public function __construct(
        private readonly ProjectTabRepository $projectTabRepository,
        private readonly CalendarService $calendarService,
        private readonly RoomService $roomService,
        private readonly CalendarController $calendarController
    ) {
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

    public function getCalendarTab(Project $project): CalendarDto
    {
        if (\request('startDate') && \request('endDate')) {
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        $calendarData = $this->calendarController->createCalendarData('', $project);
        $eventsAtAGlance = Collection::make();
        if (\request('atAGlance') === 'true') {
            $eventsAtAGlance = ProjectCalendarShowEventResource::collection(
                $this->calendarController
                    ->filterEvents($project->events(), null, null, null, $project)
                    ->with(['room','project','creator'])
                    ->orderBy('start_time', 'ASC')
                    ->get()
            )->collection->groupBy('room.id');
        }
        return $this->createCalendarDto(
            $calendarData,
            $eventsAtAGlance,
            $this->roomService->filterRooms($startDate, $endDate)->get(),
            new CalendarEventCollectionResourceModel(
                $calendarData['filterOptions']['areas'],
                $calendarData['filterOptions']['projects'],
                $calendarData['filterOptions']['eventTypes'],
                $calendarData['filterOptions']['roomCategories'],
                $calendarData['filterOptions']['roomAttributes'],
                $this->calendarController->getEventsOfInterval($startDate, $endDate, $project),
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
}
