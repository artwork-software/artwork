<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Shift\Models\ShiftPresetGroup;
use Artwork\Modules\Shift\Services\SingleShiftPresetService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ShiftPlanService
{
    public function __construct(
        private readonly CalendarDataService $calendarDataService,
        private readonly ShiftCalendarService $shiftCalendarService,
        private readonly SingleShiftPresetService $singleShiftPresetService,
        private readonly ProjectService $projectService,
    ) {
    }

    public function getMeta(Request $request): array
    {
        $shiftPlanContext = $this->buildShiftPlanContext($request);

        $roomsList = $shiftPlanContext['filteredRooms']->map(fn($room) => [
            'roomId' => $room->id,
            'roomName' => $room->name,
        ])->values()->all();

        return [
            'days' => $shiftPlanContext['calendarPeriod'],
            'rooms' => $roomsList,
            'singleShiftPresets' => $this->singleShiftPresetService->getAllPresets(),
            'shiftGroupPresets' => $this->loadShiftGroupPresets(),
        ];
    }

    public function getRoomContent(Request $request): ?array
    {
        $requestedRoomId = $request->query('room_id');
        if ($requestedRoomId === null || $requestedRoomId === '') {
            return null;
        }

        $requestedRoomId = (int)$requestedRoomId;
        $shiftPlanContext = $this->buildShiftPlanContext($request);

        $roomsForRequestedRoom = $shiftPlanContext['filteredRooms']->where('id', $requestedRoomId)->values();
        if ($roomsForRequestedRoom->isEmpty()) {
            return null;
        }

        $useDailyView = (bool)$shiftPlanContext['currentProject']
            || (bool)$shiftPlanContext['currentUser']->getAttribute('daily_view');

        $this->shiftCalendarService->filterRoomsEventsAndShifts(
            $roomsForRequestedRoom,
            $shiftPlanContext['userCalendarFilter'],
            $shiftPlanContext['calendarStartDate'],
            $shiftPlanContext['calendarEndDate'],
            $useDailyView,
            $shiftPlanContext['currentProject']
        );

        $roomsCalendarData = $this->shiftCalendarService->mapRoomsToContentForCalendar(
            $roomsForRequestedRoom,
            $shiftPlanContext['calendarStartDate'],
            $shiftPlanContext['calendarEndDate'],
        );

        $roomContent = $roomsCalendarData->rooms[0] ?? null;

        return ['room' => $roomContent];
    }

    private function buildShiftPlanContext(Request $request): array
    {
        $requestedProjectId = $request->query('projectId');
        $currentProject = !empty($requestedProjectId)
            ? $this->projectService->findById($requestedProjectId)
            : null;

        $isProjectView = $request->boolean('isInProjectView', !empty($requestedProjectId));

        /** @var User $currentUser */
        $currentUser = $request->user();

        $userCalendarSettings = $currentUser->getAttribute('calendar_settings');
        if ($userCalendarSettings === null) {
            $userCalendarSettings = $currentUser->calendar_settings()->create();
        }

        $userCalendarFilter = $currentUser->userFilters()->firstOrCreate(
            [
                'filter_type' => $isProjectView
                    ? UserFilterTypes::PROJECT_SHIFT_FILTER->value
                    : UserFilterTypes::SHIFT_FILTER->value,
            ],
            [
                'start_date' => null,
                'end_date' => null,
                'event_type_ids' => null,
                'room_ids' => null,
                'area_ids' => null,
                'room_attribute_ids' => null,
                'room_category_ids' => null,
                'event_property_ids' => null,
                'craft_ids' => null,
            ]
        );

        $requestStartDate = $request->query('start_date');
        $requestEndDate = $request->query('end_date');

        if (!empty($requestStartDate) && !empty($requestEndDate)) {
            $calendarStartDate = Carbon::parse($requestStartDate)->startOfDay();
            $calendarEndDate = Carbon::parse($requestEndDate)->endOfDay();
        } else {
            [$calendarStartDate, $calendarEndDate] = $this->calendarDataService->getCalendarDateRange(
                $userCalendarSettings,
                $userCalendarFilter,
                $currentProject
            );
        }

        $filteredRooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $calendarStartDate,
            $calendarEndDate,
            true,
            $currentProject
        );

        $calendarPeriod = $this->calendarDataService->createCalendarPeriodDto(
            $calendarStartDate,
            $calendarEndDate,
            $currentUser,
        );

        return [
            'currentProject' => $currentProject,
            'currentUser' => $currentUser,
            'userCalendarSettings' => $userCalendarSettings,
            'userCalendarFilter' => $userCalendarFilter,
            'calendarStartDate' => $calendarStartDate,
            'calendarEndDate' => $calendarEndDate,
            'calendarPeriod' => $calendarPeriod,
            'filteredRooms' => $filteredRooms,
        ];
    }

    private function loadShiftGroupPresets(): Collection
    {
        return ShiftPresetGroup::query()
            ->select(['id', 'name'])
            ->withCount('presets')
            ->with([
                'presets' => function ($query) {
                    $query->select([
                        'single_shift_presets.id',
                        'single_shift_presets.name',
                        'single_shift_presets.start_time',
                        'single_shift_presets.end_time',
                        'single_shift_presets.break_duration',
                        'single_shift_presets.craft_id',
                        'single_shift_presets.description',
                    ])->with([
                        'craft:id,name,abbreviation,color',
                        'shiftsQualifications:id,name,icon,available',
                    ]);
                },
            ])
            ->orderBy('name')
            ->get();
    }
}
