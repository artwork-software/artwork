<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Artwork\Modules\Shift\Models\SingleShiftPreset;
use Artwork\Modules\Shift\Models\ShiftPresetGroup;
use Artwork\Modules\Shift\Services\SingleShiftPresetService;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
            // Effektive Raumfarbe (Raum- oder Areal-Farbe) — analog zum Batch-Payload
            'roomColor' => $room->getEffectiveColor(),
        ])->values()->all();

        // Presets are global config (not user/date/filter specific) and change
        // rarely. Cache them with a TTL ceiling; direct mutations bust the keys
        // via model events (see SingleShiftPreset/ShiftPresetGroup::booted).
        return [
            'days' => $shiftPlanContext['calendarPeriod'],
            'rooms' => $roomsList,
            'singleShiftPresets' => Cache::remember(
                SingleShiftPreset::SHIFT_PLAN_CACHE_KEY,
                now()->addHours(12),
                fn () => $this->singleShiftPresetService->getAllPresets()
            ),
            'shiftGroupPresets' => Cache::remember(
                ShiftPresetGroup::SHIFT_PLAN_CACHE_KEY,
                now()->addHours(12),
                fn () => $this->loadShiftGroupPresets()
            ),
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
            || (bool)$shiftPlanContext['currentUser']->getAttribute('shift_plan_daily_view');

        $filterResult = $this->shiftCalendarService->filterRoomsEventsAndShifts(
            $roomsForRequestedRoom,
            $shiftPlanContext['userCalendarFilter'],
            $shiftPlanContext['calendarStartDate'],
            $shiftPlanContext['calendarEndDate'],
            $useDailyView,
            $shiftPlanContext['currentProject'],
            false,
            $shiftPlanContext['userCalendarSettings'],
            $shiftPlanContext['showUnrelatedEvents'],
            $shiftPlanContext['showUnrelatedShifts']
        );
        $roomsForRequestedRoom = $filterResult['rooms'];

        $roomsCalendarData = $this->shiftCalendarService->mapRoomsToContentForCalendar(
            $roomsForRequestedRoom,
            $shiftPlanContext['calendarStartDate'],
            $shiftPlanContext['calendarEndDate'],
        );

        $roomContent = $roomsCalendarData->rooms[0] ?? null;

        return [
            'room' => $roomContent,
            'lookups' => $filterResult['lookups'],
        ];
    }

    public function getAllRoomsContent(Request $request): array
    {
        $shiftPlanContext = $this->buildShiftPlanContext($request);
        $filteredRooms = $shiftPlanContext['filteredRooms'];

        if ($filteredRooms->isEmpty()) {
            return ['rooms' => []];
        }

        $useDailyView = (bool)$shiftPlanContext['currentProject']
            || (bool)$shiftPlanContext['currentUser']->getAttribute('shift_plan_daily_view');

        $filterResult = $this->shiftCalendarService->filterRoomsEventsAndShifts(
            $filteredRooms,
            $shiftPlanContext['userCalendarFilter'],
            $shiftPlanContext['calendarStartDate'],
            $shiftPlanContext['calendarEndDate'],
            $useDailyView,
            $shiftPlanContext['currentProject'],
            false,
            $shiftPlanContext['userCalendarSettings'],
            $shiftPlanContext['showUnrelatedEvents'],
            $shiftPlanContext['showUnrelatedShifts']
        );
        $filteredRooms = $filterResult['rooms'];

        $roomsCalendarData = $this->shiftCalendarService->mapRoomsToContentForCalendar(
            $filteredRooms,
            $shiftPlanContext['calendarStartDate'],
            $shiftPlanContext['calendarEndDate'],
        );

        return [
            'rooms' => $roomsCalendarData->rooms,
            'lookups' => $filterResult['lookups'],
            // Personenfilter "nur Personen mit offenen Regelverstößen" in der Tagesansicht:
            // die Tagesansicht lädt keine Worker-Zeilen, daher hier je User/Tag die offenen Verstöße
            'openViolationsByUser' => $this->openViolationsByUser(
                $shiftPlanContext['calendarStartDate'],
                $shiftPlanContext['calendarEndDate']
            ),
        ];
    }

    /**
     * Offene (status=active) Regelverstöße im Zeitraum, user_id => ['Y-m-d' => Anzahl].
     *
     * @return array<int, array<string, int>>
     */
    public function openViolationsByUser(Carbon $start, Carbon $end): array
    {
        $result = [];
        $rows = ShiftRuleViolation::query()
            ->select(['user_id', 'violation_date'])
            ->where('status', 'active')
            ->whereBetween('violation_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        foreach ($rows as $row) {
            $day = $row->violation_date instanceof \DateTimeInterface
                ? $row->violation_date->format('Y-m-d')
                : substr((string) $row->violation_date, 0, 10);
            $result[$row->user_id][$day] = ($result[$row->user_id][$day] ?? 0) + 1;
        }

        return $result;
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

        $isDailyView = !$isProjectView && (bool) $currentUser->getAttribute('shift_plan_daily_view');

        // "Projektfremde Termine/Schichten anzeigen" (nur im Projekt-Schichten-Tab relevant):
        // Das Zahnrad im Projekt-Tab schreibt in shift_plan_daily_settings (is_daily_view=true),
        // daher werden die Flags bewusst dort gelesen — nicht aus den shift_plan_settings,
        // die die Projektansicht sonst als Anzeige-Settings nutzt.
        $showUnrelatedEvents = false;
        $showUnrelatedShifts = false;
        if ($isProjectView && $currentProject !== null) {
            $projectViewSettings = $currentUser->getAttribute('shift_plan_daily_settings');
            $showUnrelatedEvents = (bool) ($projectViewSettings?->show_unrelated_events ?? false);
            $showUnrelatedShifts = (bool) ($projectViewSettings?->show_unrelated_shifts ?? false);
        }

        // firstOrCreate statt create: meta- und rooms.batch-Request laufen parallel
        // und können hier gleichzeitig ohne Settings-Zeile ankommen — der Verlierer
        // liest dank unique(user_id) die Zeile des Gewinners statt eine Dublette
        // anzulegen.
        if ($isDailyView) {
            $userCalendarSettings = $currentUser->getAttribute('shift_plan_daily_settings')
                ?? $currentUser->shift_plan_daily_settings()->firstOrCreate();
        } else {
            $userCalendarSettings = $currentUser->getAttribute('shift_plan_settings')
                ?? $currentUser->shift_plan_settings()->firstOrCreate();
        }

        $shiftFilterType = $isProjectView
            ? UserFilterTypes::PROJECT_SHIFT_FILTER->value
            : ($isDailyView
                ? UserFilterTypes::SHIFT_DAILY_FILTER->value
                : UserFilterTypes::SHIFT_FILTER->value);

        $userCalendarFilter = $currentUser->userFilters()->firstOrCreate(
            [
                'filter_type' => $shiftFilterType,
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
            $currentProject,
            $showUnrelatedEvents,
            $showUnrelatedShifts
        );

        $calendarPeriod = $this->calendarDataService->createCalendarPeriodDto(
            $calendarStartDate,
            $calendarEndDate,
            $currentUser,
            true,
            $isDailyView
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
            'showUnrelatedEvents' => $showUnrelatedEvents,
            'showUnrelatedShifts' => $showUnrelatedShifts,
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
