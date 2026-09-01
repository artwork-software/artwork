<?php

namespace App\Http\Controllers;

use Artwork\Core\FileHandling\Naming\DownloadFileName;
use Artwork\Core\FileHandling\Naming\StoredFileName;
use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Calendar\Services\EventExportDisplaySettings;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\Event\Services\EventService;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Holidays\Models\Holiday;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\Shift\Services\DailyShiftPlanPdfBuilder;
use Artwork\Modules\User\Enums\UserFilterTypes;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Policies\UserPolicy;
use Artwork\Modules\User\Services\UserService;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Auth\AuthManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\UrlGenerator;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportPDFController extends Controller
{
    public function __construct(
        private readonly CalendarDataService $calendarDataService,
        protected ProjectService $projectService,
        protected RoomService $roomService,
        protected UserService $userService,
        protected FilesystemManager $filesystemManager,
        protected InertiaResponseFactory $inertiaResponseFactory,
        protected UrlGenerator $urlGenerator,
        protected PdfWrapper $snappyPdf,
        protected AuthManager $authManager,
        protected EventCalendarService $eventCalendarService,
        protected ShiftCalendarService $shiftCalendarService,
        protected DailyShiftPlanPdfBuilder $dailyShiftPlanPdfBuilder,
    ) {
    }

    public function createPDF(Request $request): Response
    {
        /** @var User $user */
        $user = $this->authManager->guard()->user();
        $userFilter = $user->userFilters()->calendarFilter()->first();

        $projectId = $request->get('project');

        $startDate = $request->get('start') ?
            Carbon::parse($request->get('start'))->startOfDay() :
            $userFilter->start_date;

        $endDate = $request->get('end') ?
            Carbon::parse($request->get('end'))->endOfDay() :
            $userFilter->end_date;


        // Anzeigeeinstellungen: Kalender-Settings des Users als Default, Export-Modal
        // darf pro Export übersteuern; wirkt auch auf Raum-/Terminselektion
        $displaySettings = EventExportDisplaySettings::fromRequest(
            $this->resolveDisplaySettingsInput($request),
            $user->getAttribute('calendar_settings')
        );
        $userCalendarSettings = $displaySettings->settings();
        $filterData   = $request->filter;

        $userCalendarFilter = new UserFilter($filterData);
        $userCalendarFilter->exists = false;

        // Falls nur Projekt angegeben -> Zeitspanne aus dem Projekt ableiten
        if ($projectId) {
            $today = \Carbon\Carbon::now();
            $project = $this->projectService->findById($projectId);

            [$startDate, $endDate] = $this->calendarDataService->getProjectDateRange($project, $today);
        }


        $filteredEventTypes = EventType::whereIn('id', $userCalendarFilter->event_type_ids ?? [])
            ->get()->pluck('name')->toArray();

        $filteredRooms = Room::whereIn('id', $userCalendarFilter->room_ids ?? [])
            ->get()->pluck('name')->toArray();

        $filteredEventProperties = EventProperty::whereIn('id', $userCalendarFilter->event_property_ids ?? [])
            ->get()->pluck('name')->toArray();

        $filteredRoomAttributes = RoomAttribute::whereIn('id', $userCalendarFilter->room_attribute_ids ?? [])
            ->get()->pluck('name')->toArray();

        $filteredAreas = Area::whereIn('id', $userCalendarFilter->area_ids ?? [])
            ->get()->pluck('name')->toArray();

        $startDate = \Carbon\Carbon::parse($startDate)->startOfDay();
        $endDate   = \Carbon\Carbon::parse($endDate)->endOfDay();

        // Räume anhand Filter
        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
        );

        // Calendar DTO (rooms[]= ['roomId'=>..,'content'=>['29.10.2025'=>['events'=>[...]]]])
        $calendar = $this->eventCalendarService->mapRoomsToContentForCalendar(
            $this->eventCalendarService->filterRoomsEventsForPdf(
                $rooms,
                $userCalendarFilter,
                $startDate,
                $endDate,
                $userCalendarSettings,
                $displaySettings
            ),
            $startDate,
            $endDate
        );

        // Lookup: roomId -> content (O(1) statt O(n) im Blade)
        $calendarLookup = [];
        foreach (($calendar->rooms ?? []) as $roomBlock) {
            $rid = $roomBlock['roomId'] ?? ($roomBlock->roomId ?? null);
            $content = $roomBlock['content'] ?? ($roomBlock->content ?? []);
            if ($rid !== null) {
                $calendarLookup[$rid] = $content;
            }
        }

        // Liste der Tage bauen
        $days = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $days[] = [
                'fullDay'    => $cursor->format('d.m.Y'),           // "29.10.2025"
                'dayString'  => $cursor->translatedFormat('D.'),    // "Mi."
                'weekNumber' => $cursor->isoWeek(),
                'isWeekend'  => $cursor->isWeekend(),
            ];
            $cursor->addDay();
        }

        // Tagesbemerkungen (optional, keyed by d.m.Y wie die Tages-Header) —
        // nur wenn angefordert, Feature aktiv und der User sie sehen darf
        $dayRemarks = [];
        if (
            $displaySettings->shows('show_day_remarks')
            && app(\App\Settings\GeneralCalendarSettings::class)->day_remarks_enabled
            && (
                // Gleiche Sichtbarkeitsregel wie im Kalender (CalendarDataService):
                // EDIT impliziert Sehen-Dürfen.
                $user->can(\Artwork\Modules\Permission\Enums\PermissionEnum::DAY_REMARKS_VIEW->value)
                || $user->can(\Artwork\Modules\Permission\Enums\PermissionEnum::DAY_REMARKS_EDIT->value)
            )
        ) {
            $dayRemarks = \Artwork\Modules\Calendar\Models\DayRemark::query()
                ->betweenDates($startDate, $endDate)
                ->get()
                ->mapWithKeys(static fn ($dayRemark) => [$dayRemark->date->format('d.m.Y') => $dayRemark->remark])
                ->all();
        }

        // Horizontaler Chunk: wie viele Tage pro Seite sichtbar sein sollen
        $DAYS_PER_PAGE = $request->integer('daysPerPage') ?: 7;
        $dayChunks = array_chunk($days, $DAYS_PER_PAGE);

        // Vertikaler Chunk: wie viele Räume pro Seite
        $roomChunks   = $rooms->chunk(8)->values();

        // Für den Header
        $project = $projectId ? $this->projectService->findById($projectId) : null;

        // Determine export mode: 'relative' (new) or 'block' (old)
        $exportMode = $request->get('exportMode', 'relative');

        $rowHeights = [];

        if ($exportMode === 'block') {
            // Old export mode: dynamic row heights based on event count per slot
            try {
                $perEventHeight = 22; // px, entspricht ungefähr der Mindesthöhe eines Event-Bubbles inkl. Margin
                $baseMinHeight  = 36; // px, Mindesthöhe wenn keine Events vorhanden sind

                // Liste der Tag-Strings (Format wie im View: d.m.Y)
                $allDayStrings = array_map(static fn ($d) => $d['fullDay'], $days);

                foreach ($rooms as $room) {
                    $rid = $room->id;
                    $maxPerSlot = [
                        'morning' => 0,
                        'noon'    => 0,
                        'evening' => 0,
                    ];

                    $roomContent = $calendarLookup[$rid] ?? null;
                    if (!$roomContent) {
                        $rowHeights[$rid] = [
                            'morning' => $baseMinHeight,
                            'noon'    => $baseMinHeight,
                            'evening' => $baseMinHeight,
                        ];
                        continue;
                    }

                    foreach ($allDayStrings as $dayDisplay) {
                        $events = $roomContent[$dayDisplay]['events'] ?? [];
                        if (empty($events)) {
                            // Keine Events für diesen Tag
                            continue;
                        }

                        // Zähle Events, die je Slot in diesen Tag fallen
                        foreach (['morning', 'noon', 'evening'] as $slot) {
                            $count = 0;
                            foreach ($events as $event) {
                                if (self::eventOverlapsSlot($event, $dayDisplay, $slot)) {
                                    $count++;
                                }
                            }
                            if ($count > $maxPerSlot[$slot]) {
                                $maxPerSlot[$slot] = $count;
                            }
                        }
                    }

                    // Berechne Pixelhöhen pro Slot
                    $rowHeights[$rid] = [
                        'morning' => max($baseMinHeight, $maxPerSlot['morning'] * $perEventHeight),
                        'noon'    => max($baseMinHeight, $maxPerSlot['noon']    * $perEventHeight),
                        'evening' => max($baseMinHeight, $maxPerSlot['evening'] * $perEventHeight),
                    ];
                }
            } catch (\Throwable $e) {
                // Fallback, falls Struktur sich ändert – View erhält dann nur Basiswerte
                $rowHeights = [];
            }
        } else {
            // Compact stacking mode: Events werden kompakt gestapelt, nicht zeit-proportional.
            // Slot-Höhe = Summe der Event-Höhen in der vollsten Lane + Gaps.
            $baseSegmentHeight = 40;  // px minimum per slot
            $maxSegmentHeight  = 400; // px cap to prevent absurdly tall rows
            $baseCharsPerLine  = 14;  // chars per line at full column width (single lane)
            $titleLineH        = 14;  // .event-title: 11px * 1.2 + spacing
            $projectLineH      = 14;  // .event-sub:   10px * 1.2 + margin
            $timeLineH         = 12;  // .event-time:   8px * 1.15 + margin
            $paddingPx         = 14;  // .event-inner padding + borders + extra breathing room
            $GAP_PX            = 4;   // gap between non-consecutive events

            $allDayStrings = array_map(static fn ($d) => $d['fullDay'], $days);

            foreach ($rooms as $room) {
                $rid = $room->id;
                $roomContent = $calendarLookup[$rid] ?? null;

                $slotMaxHeight = [
                    'morning' => $baseSegmentHeight,
                    'noon'    => $baseSegmentHeight,
                    'evening' => $baseSegmentHeight,
                ];

                if ($roomContent) {
                    foreach ($allDayStrings as $dayDisplay) {
                        $events = $roomContent[$dayDisplay]['events'] ?? [];
                        if (empty($events)) {
                            continue;
                        }

                        // Count events per slot and determine lane count
                        $slotCounts = ['morning' => 0, 'noon' => 0, 'evening' => 0];
                        foreach ($events as $event) {
                            foreach (['morning', 'noon', 'evening'] as $slot) {
                                if (self::eventOverlapsSlot($event, $dayDisplay, $slot)) {
                                    $slotCounts[$slot]++;
                                }
                            }
                        }
                        $laneCount = max(1, max($slotCounts['morning'], $slotCounts['noon'], $slotCounts['evening']));

                        $effectiveCharsPerLine = max(4, (int) floor($baseCharsPerLine / $laneCount));

                        // Collect content heights per slot
                        $slotContentHeights = ['morning' => [], 'noon' => [], 'evening' => []];
                        $tz = config('app.timezone');
                        foreach ($events as $event) {
                            $start    = \Illuminate\Support\Carbon::parse($event->start)->timezone($tz);
                            $startMin = max(360, min(1440, ((int) $start->format('H')) * 60 + ((int) $start->format('i'))));
                            $allDay   = (bool) ($event->allDay ?? false);

                            $slot = $allDay ? 'morning' : ($startMin < 720 ? 'morning' : ($startMin < 1080 ? 'noon' : 'evening'));

                            // Gleiche Namens-/Zeilenlogik wie __buildSegmentForDay im Blade,
                            // damit die vorberechneten Slot-Höhen zum Rendering passen
                            $name        = $displaySettings->resolveEventName(
                                $event->eventName ?? null,
                                $event->artistNames ?? null
                            ) ?? '';
                            $abbr        = $event->eventType?->abbreviation ?? '';
                            $projectName = $event->project->name ?? '';

                            $titleText    = ($abbr !== '' ? $abbr . ': ' : '') . $name;
                            $titleLines   = max(1, (int) ceil(mb_strlen($titleText) / $effectiveCharsPerLine));
                            $projectLines = $projectName !== '' ? max(1, (int) ceil(mb_strlen($projectName) / $effectiveCharsPerLine)) : 0;
                            $contentHeight = max(40,
                                $titleLines * $titleLineH
                                + $projectLines * $projectLineH
                                + $timeLineH
                                + $paddingPx
                            );
                            foreach ($displaySettings->extraContentLines($event) as $extraLine) {
                                $contentHeight += max(1, (int) ceil(mb_strlen($extraLine) / $effectiveCharsPerLine))
                                    * $projectLineH;
                            }

                            $slotContentHeights[$slot][] = $contentHeight;
                        }

                        // Compute required slot heights based on compact stacking
                        foreach (['morning', 'noon', 'evening'] as $slot) {
                            $heights = $slotContentHeights[$slot];
                            $n = count($heights);
                            if ($n === 0) {
                                continue;
                            }

                            // Approximate events per lane (worst case for tallest lane)
                            $lanesInSlot = min($slotCounts[$slot], $laneCount);
                            $eventsPerLane = (int) ceil($n / max(1, $lanesInSlot));

                            // Use largest content heights for tallest lane estimate
                            rsort($heights);
                            $stackHeight = 0;
                            for ($i = 0; $i < min($eventsPerLane, $n); $i++) {
                                $stackHeight += $heights[$i];
                            }
                            $stackHeight += max(0, min($eventsPerLane, $n) - 1) * $GAP_PX;
                            $stackHeight = min($stackHeight, $maxSegmentHeight);

                            if ($stackHeight > $slotMaxHeight[$slot]) {
                                $slotMaxHeight[$slot] = $stackHeight;
                            }
                        }
                    }
                }

                $rowHeights[$rid] = $slotMaxHeight;
            }
        }

        // Select blade template based on export mode
        $bladeTemplate = $exportMode === 'block' ? 'pdf.calendarExportNotRelative' : 'pdf.calendar';

        // PDF rendern
        $pdf = $this->snappyPdf->loadView(
            $bladeTemplate,
            [
                'title'          => $request->get('title') ?? 'Raumbelegung',
                'project'        => $project,
                'user_filters'   => $userCalendarFilter,
                'created_by'     => $user->full_name,
                'calendar'       => $calendar,     // CalendarFrontendDataDTO
                'calendarLookup' => $calendarLookup, // roomId -> content (O(1) Lookup)
                'roomChunks'     => $roomChunks,   // Collection pro vertikaler Seite
                'dayChunks'      => $dayChunks,    // Array pro horizontaler Seite
                'activeFilter'  => [
                    'event_types'      => $filteredEventTypes,
                    'rooms'            => $filteredRooms,
                    'event_properties' => $filteredEventProperties,
                    'room_attributes'  => $filteredRoomAttributes,
                    'areas'            => $filteredAreas,
                ],
                'DAYS_PER_PAGE'  => $DAYS_PER_PAGE,
                'rowHeights'     => $rowHeights,   // Einheitliche Mindesthöhen pro Raum+Slot
                'display'        => $displaySettings,
                'paperSize'      => $request->string('paperSize', 'a4'),
                'dayRemarks'     => $dayRemarks,   // d.m.Y => Bemerkungstext (leer wenn nicht angefordert)
            ]
        )
            ->setPaper(
                $request->string('paperSize'),
                $request->string('paperOrientation')
            )
            ->setOption('dpi', (int) $request->float('dpi'));

        $filename = $this->createFilename();

        if ($this->filesystemManager->directoryMissing('pdf')) {
            $this->filesystemManager->makeDirectory('pdf');
        }

        $pdf->save($this->createStoragePath($this->filesystemManager, $filename));

        return $this->inertiaResponseFactory->location(
            $this->urlGenerator->route(
                'calendar.export.pdf.download',
                ['filename' => $filename, 'name' => $this->createDownloadName()]
            )
        );
    }


    /**
     * Exports the currently displayed shift plan view (date range, active filters and project mode
     * are taken over from the request, mirroring the parameters used by shiftPlanEventAPI) into a PDF.
     * Each ISO calendar week is rendered on its own page.
     */
    public function createShiftPlanPDF(Request $request): Response
    {
        /** @var User $user */
        $user = $this->authManager->guard()->user();

        $projectId = $request->get('projectId');
        $project = !empty($projectId) ? $this->projectService->findById($projectId) : null;

        // Mirror shiftPlanEventAPI: project view forces non-daily, otherwise respect the user's daily flag.
        $isInProjectView = $request->boolean('isInProjectView', !empty($projectId));
        $isDailyView = !$isInProjectView
            && $request->boolean('isDailyView', (bool) $user->getAttribute('calendar_daily_view'));

        if ($isDailyView) {
            $userCalendarSettings = $user->getAttribute('daily_view_calendar_settings')
                ?? $user->daily_view_calendar_settings()->create();
        } else {
            $userCalendarSettings = $user->getAttribute('calendar_settings')
                ?? $user->calendar_settings()->create();
        }

        $shiftFilterType = $isInProjectView
            ? UserFilterTypes::PROJECT_SHIFT_FILTER->value
            : ($isDailyView
                ? UserFilterTypes::SHIFT_DAILY_FILTER->value
                : UserFilterTypes::SHIFT_FILTER->value);

        $userCalendarFilter = $user->userFilters()->firstOrCreate(
            ['filter_type' => $shiftFilterType],
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

        // The export dialog may override single filter dimensions (prefilled with the active shift
        // plan filters, adjustable before export). Overrides are applied in-memory only — the
        // user's saved shift plan filter must not change. Keys absent from the request keep the
        // saved filter value, an empty array clears that dimension (= no filter).
        foreach (
            [
                'event_type_ids',
                'room_ids',
                'area_ids',
                'room_attribute_ids',
                'room_category_ids',
                'event_property_ids',
                'craft_ids',
            ] as $filterKey
        ) {
            if ($request->exists($filterKey)) {
                $values = $request->input($filterKey);
                $userCalendarFilter->setAttribute(
                    $filterKey,
                    is_array($values) && $values !== []
                        ? array_values(array_map('intval', $values))
                        : null
                );
            }
        }

        // Respect the date range currently shown in the shift plan (sent by the frontend).
        $startDateParam = $request->get('start');
        $endDateParam = $request->get('end');

        if (!empty($startDateParam) && !empty($endDateParam)) {
            $startDate = Carbon::parse($startDateParam)->startOfDay();
            $endDate = Carbon::parse($endDateParam)->endOfDay();
        } else {
            [$startDate, $endDate] = $this->calendarDataService
                ->getCalendarDateRange($userCalendarSettings, $userCalendarFilter, $project);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
        }

        if ($endDate->lessThan($startDate)) {
            $endDate = $startDate->copy()->endOfDay();
        }

        // Mirror the six-month cap of the shift plan view so a single export stays renderable.
        if ($startDate->diffInDays($endDate) > 183) {
            $endDate = $startDate->copy()->addDays(183)->endOfDay();
        }

        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $startDate,
            $endDate,
            true,
            $project
        );

        $filterResult = $this->shiftCalendarService->filterRoomsEventsAndShifts(
            $rooms,
            $userCalendarFilter,
            $startDate,
            $endDate,
            false,
            $project,
            false
        );
        $rooms = $filterResult['rooms'];
        $lookups = $filterResult['lookups'] ?? [];

        $calendar = $this->shiftCalendarService->mapRoomsToContentForCalendar(
            $rooms,
            $startDate,
            $endDate
        );

        // Normalize the room blocks to plain nested arrays (identical to the JSON the frontend
        // receives) so the blade can rely on consistent array access for rooms, events and shifts.
        $normalizedRooms = json_decode(json_encode($calendar->rooms ?? []), true) ?: [];

        // Lookup: roomId -> room data (content + eventsById + shiftsById) for O(1) blade access.
        $roomLookup = [];
        foreach ($normalizedRooms as $roomBlock) {
            $rid = $roomBlock['roomId'] ?? null;
            if ($rid !== null) {
                $roomLookup[$rid] = $roomBlock;
            }
        }

        // Optionally drop rooms without a single event or shift in the exported period —
        // over long periods this keeps the PDF focused on rooms that actually carry content.
        $hideEmptyRooms = $request->boolean('hideEmptyRooms');
        if ($hideEmptyRooms) {
            $rooms = $rooms->filter(function ($room) use ($roomLookup): bool {
                foreach (($roomLookup[$room->id]['content'] ?? []) as $cell) {
                    if (!empty($cell['eventIds']) || !empty($cell['shiftIds'])) {
                        return true;
                    }
                }
                return false;
            })->values();
        }

        // Build the list of days and group them by ISO calendar week (one week = one page).
        $weeks = [];
        $cursor = $startDate->copy()->startOfDay();
        $lastDay = $endDate->copy()->startOfDay();
        while ($cursor->lte($lastDay)) {
            $weekKey = $cursor->isoWeekYear . '-' . str_pad((string) $cursor->isoWeek, 2, '0', STR_PAD_LEFT);
            if (!isset($weeks[$weekKey])) {
                $weeks[$weekKey] = [
                    'weekNumber' => $cursor->isoWeek,
                    'weekYear' => $cursor->isoWeekYear,
                    'days' => [],
                ];
            }
            $weeks[$weekKey]['days'][] = [
                'fullDay' => $cursor->format('d.m.Y'),
                'dayString' => $cursor->translatedFormat('D'),
                'longDay' => $cursor->translatedFormat('l'),
                'isWeekend' => $cursor->isWeekend(),
                'isToday' => $cursor->isToday(),
            ];
            $cursor->addDay();
        }
        $weeks = array_values($weeks);

        // Human-readable summary of the active filters for the header.
        $activeFilter = [
            'rooms' => Room::whereIn('id', $userCalendarFilter->room_ids ?? [])->pluck('name')->toArray(),
            'areas' => Area::whereIn('id', $userCalendarFilter->area_ids ?? [])->pluck('name')->toArray(),
            'event_types' => EventType::whereIn('id', $userCalendarFilter->event_type_ids ?? [])->pluck('name')->toArray(),
            'crafts' => Craft::whereIn('id', $userCalendarFilter->craft_ids ?? [])->pluck('name')->toArray(),
        ];

        $title = $request->get('title');
        if (empty($title)) {
            $title = $project ? $project->name : __('Shift plan');
        }

        // In project mode the shifts/events of the active time-period project are highlighted.
        $highlightProjectId = $request->get('highlightProjectId');
        $highlightProjectId = ($highlightProjectId !== null && $highlightProjectId !== '')
            ? (int) $highlightProjectId
            : null;
        $highlightProjectName = $highlightProjectId
            ? optional($this->projectService->findById($highlightProjectId))->name
            : null;

        // Kalenderwochen des Zeitraums für den Kopfbereich, damit ein nach KW
        // gefilterter Export als solcher erkennbar ist.
        $kwRange = ($startDate->isoWeek === $endDate->isoWeek && $startDate->isoWeekYear === $endDate->isoWeekYear)
            ? sprintf('KW %d/%d', $startDate->isoWeek, $startDate->isoWeekYear)
            : sprintf(
                'KW %d/%d – KW %d/%d',
                $startDate->isoWeek,
                $startDate->isoWeekYear,
                $endDate->isoWeek,
                $endDate->isoWeekYear
            );

        $pdf = $this->snappyPdf->loadView(
            'pdf.shiftplan_export',
            [
                'title' => $title,
                'project' => $project,
                'weeks' => $weeks,
                'rooms' => $rooms,
                'roomLookup' => $roomLookup,
                'eventTypesById' => $lookups['eventTypesById'] ?? [],
                'activeFilter' => $activeFilter,
                'created_by' => $user->full_name,
                'startDate' => $startDate->format('d.m.Y'),
                'endDate' => $endDate->format('d.m.Y'),
                'kwRange' => $kwRange,
                'highlightProjectId' => $highlightProjectId,
                'highlightProjectName' => $highlightProjectName,
                'hideEmptyRooms' => $hideEmptyRooms,
            ]
        )
            ->setPaper(
                $request->string('paperSize', 'a4'),
                $request->string('paperOrientation', 'landscape')
            )
            ->setOption('dpi', (int) ($request->float('dpi') ?: 96));

        $filename = $this->createFilename();

        if ($this->filesystemManager->directoryMissing('pdf')) {
            $this->filesystemManager->makeDirectory('pdf');
        }

        $pdf->save($this->createStoragePath($this->filesystemManager, $filename));

        return $this->inertiaResponseFactory->location(
            $this->urlGenerator->route(
                'calendar.export.pdf.download',
                ['filename' => $filename, 'name' => $this->createDownloadName()]
            )
        );
    }

    public static function eventOverlapsSlot($event, string $dayDisplay, string $slot): bool
    {
        if (!empty($event->allDay)) {
            return true;
        }

        if (empty($event->start) || empty($event->end)) {
            return false;
        }

        // Slot-Grenzen als Stunden
        $slotBounds = ['morning' => [0, 12], 'noon' => [12, 18], 'evening' => [18, 24]];
        [$slotStartH, $slotEndH] = $slotBounds[$slot] ?? [0, 24];

        $day = \Carbon\Carbon::createFromFormat('d.m.Y', $dayDisplay)->startOfDay();
        $slotStartTs = $day->copy()->addHours($slotStartH)->getTimestamp();
        $slotEndTs   = $slot === 'evening'
            ? $day->copy()->setTime(23, 59, 59)->getTimestamp()
            : $day->copy()->addHours($slotEndH)->getTimestamp();

        $eventStartTs = \Carbon\Carbon::parse($event->start)->getTimestamp();
        $eventEndTs   = \Carbon\Carbon::parse($event->end)->getTimestamp();

        return $eventStartTs < $slotEndTs && $eventEndTs > $slotStartTs;
    }


    /**
     * `displaySettings` aus dem Request; alte Payloads (colorSource/includeDayRemarks)
     * werden auf die entsprechenden Flags gemappt, damit noch offene Tabs weiter funktionieren.
     *
     * @return array<string, bool>|null
     */
    private function resolveDisplaySettingsInput(Request $request): ?array
    {
        $input = $request->input('displaySettings');
        if (is_array($input)) {
            return $input;
        }

        $legacy = [];
        if ($request->has('colorSource')) {
            $legacy['use_main_category_color'] = $request->get('colorSource') === 'mainCategory';
            $legacy['use_event_status_color'] = false;
        }
        if ($request->has('includeDayRemarks')) {
            $legacy['show_day_remarks'] = $request->boolean('includeDayRemarks');
        }

        return $legacy !== [] ? $legacy : null;
    }

    /**
     * Normalisiert Monats-Eingaben auf "YYYY-MM". Akzeptiert "YYYY-MM", "YYYY-MM-DD",
     * "MM.YYYY" und "DD.MM.YYYY"; alles andere ergibt null (= Fallback aktueller Monat).
     */
    private function normalizeMonthInput(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^(\d{4})-(\d{2})-\d{2}/', $value, $matches)) {
            return $matches[1] . '-' . $matches[2];
        }
        if (preg_match('/^(\d{1,2})\.(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d', $matches[2], $matches[1]);
        }
        if (preg_match('/^\d{1,2}\.(\d{1,2})\.(\d{4})$/', $value, $matches)) {
            return sprintf('%04d-%02d', $matches[2], $matches[1]);
        }

        return null;
    }

    public function createMonthlyPDF(Request $request): Response
    {
        /** @var User $user */
        $user = $this->authManager->guard()->user();
        $userFilter = $user->userFilters()->calendarFilter()->first();

        $projectId = $request->get('project');
        $displaySettings = EventExportDisplaySettings::fromRequest(
            $this->resolveDisplaySettingsInput($request),
            $user->getAttribute('calendar_settings')
        );
        $userCalendarSettings = $displaySettings->settings();
        $filterData = $request->filter;
        $userCalendarFilter = new UserFilter($filterData);
        $userCalendarFilter->exists = false;

        // Determine months to export
        $months = []; // array of ['start' => Carbon, 'end' => Carbon]

        if ($projectId) {
            $project = $this->projectService->findById($projectId);
            $today = Carbon::now();
            [$projectStart, $projectEnd] = $this->calendarDataService->getProjectDateRange($project, $today);
            $projectStart = Carbon::parse($projectStart)->startOfMonth();
            $projectEnd = Carbon::parse($projectEnd)->endOfMonth();

            $cursor = $projectStart->copy();
            while ($cursor->lte($projectEnd)) {
                $months[] = [
                    'start' => $cursor->copy()->startOfMonth(),
                    'end' => $cursor->copy()->endOfMonth(),
                ];
                $cursor->addMonth();
            }
        } else {
            // User-Eingaben können je nach Browser/Locale auch "10.2025", "15.10.2025" oder
            // "2025-10-15" sein – auf "YYYY-MM" normalisieren statt mit 500 zu crashen
            $startMonth = $this->normalizeMonthInput($request->get('startMonth'));
            $endMonth = $this->normalizeMonthInput($request->get('endMonth'));

            if ($startMonth) {
                $start = Carbon::parse($startMonth . '-01')->startOfMonth();
                $end = $endMonth
                    ? Carbon::parse($endMonth . '-01')->endOfMonth()
                    : $start->copy()->endOfMonth();

                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $months[] = [
                        'start' => $cursor->copy()->startOfMonth(),
                        'end' => $cursor->copy()->endOfMonth(),
                    ];
                    $cursor->addMonth();
                }
            } else {
                // Fallback: current month
                $months[] = [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth(),
                ];
            }
        }

        // Get the full date range for fetching rooms and events
        $globalStart = $months[0]['start']->copy();
        $globalEnd = end($months)['end']->copy();

        // Get filtered rooms
        $rooms = $this->calendarDataService->getFilteredRooms(
            $userCalendarFilter,
            $userCalendarSettings,
            $globalStart,
            $globalEnd,
        );

        // Get calendar data
        $calendar = $this->eventCalendarService->mapRoomsToContentForCalendar(
            $this->eventCalendarService->filterRoomsEventsForPdf(
                $rooms,
                $userCalendarFilter,
                $globalStart,
                $globalEnd,
                $userCalendarSettings,
                $displaySettings
            ),
            $globalStart,
            $globalEnd
        );

        // Build lookup: roomId -> content
        $calendarLookup = [];
        foreach (($calendar->rooms ?? []) as $roomBlock) {
            $rid = $roomBlock['roomId'] ?? ($roomBlock->roomId ?? null);
            $content = $roomBlock['content'] ?? ($roomBlock->content ?? []);
            if ($rid !== null) {
                $calendarLookup[$rid] = $content;
            }
        }

        // Build pages data: one page per month
        $pages = [];
        $dayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

        foreach ($months as $monthData) {
            $monthStart = $monthData['start'];
            $monthEnd = $monthData['end'];
            $days = [];
            $cursor = $monthStart->copy();
            while ($cursor->lte($monthEnd)) {
                $days[] = [
                    'fullDay' => $cursor->format('d.m.Y'),
                    'display' => $dayNames[$cursor->dayOfWeekIso - 1] . ', ' . $cursor->format('d.m'),
                    'isWeekend' => $cursor->isWeekend(),
                ];
                $cursor->addDay();
            }
            $pages[] = [
                'monthLabel' => $monthStart->translatedFormat('F Y'),
                'days' => $days,
            ];
        }

        // Big logo as base64
        $generalSettings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);
        $bigLogoBase64 = null;
        if ($generalSettings->big_logo_path) {
            $storage = $this->filesystemManager->disk('public');
            if ($storage->exists($generalSettings->big_logo_path)) {
                $logoContent = $storage->get($generalSettings->big_logo_path);
                $mimeType = $storage->mimeType($generalSettings->big_logo_path);
                $bigLogoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
            }
        }

        $project = $projectId ? $this->projectService->findById($projectId) : null;

        $pdf = $this->snappyPdf->loadView(
            'pdf.calendarMonthlyOverview',
            [
                'title' => $request->get('title') ?? 'Monatsübersicht',
                'project' => $project,
                'rooms' => $rooms,
                'calendarLookup' => $calendarLookup,
                'pages' => $pages,
                'created_by' => $user->first_name . ' ' . $user->last_name,
                'created_date' => Carbon::now()->format('d.m.Y'),
                'bigLogoBase64' => $bigLogoBase64,
                'display' => $displaySettings,
                'paperSize' => $request->string('paperSize', 'a3'),
            ]
        )
            ->setPaper(
                $request->string('paperSize', 'a3'),
                $request->string('paperOrientation', 'landscape')
            )
            ->setOption('dpi', (int) $request->float('dpi', 72));

        $filename = $this->createFilename();

        if ($this->filesystemManager->directoryMissing('pdf')) {
            $this->filesystemManager->makeDirectory('pdf');
        }

        $pdf->save($this->createStoragePath($this->filesystemManager, $filename));

        return $this->inertiaResponseFactory->location(
            $this->urlGenerator->route(
                'calendar.export.pdf.download',
                ['filename' => $filename, 'name' => $this->createDownloadName()]
            )
        );
    }

    /**
     * Persönlicher Einsatzplan als Monatsübersicht (Outlook-Stil): 1 Monat = 1 Seite,
     * KW-Zeilen, Mo–So-Spalten. Schichten in Gewerk-Farbe, individuelle Zeiten in Grau.
     */
    public function createUserShiftPlanPDF(
        Request $request,
        int $user,
        EventService $eventService
    ): Response {
        $authUser = $this->authManager->guard()->user();

        $type = $request->string('type', 'user')->toString();
        $modelId = (int) ($request->integer('model_id') ?: $user);

        // {user} ist je nach type eine User-, Freelancer- oder Dienstleister-ID –
        // deshalb bewusst kein Route-Model-Binding auf User (404 bei Freelancer-IDs).
        $worker = match ($type) {
            'freelancer' => \Artwork\Modules\Freelancer\Models\Freelancer::query()->findOrFail($modelId),
            'service_provider', 'serviceProvider' =>
                \Artwork\Modules\ServiceProvider\Models\ServiceProvider::query()->findOrFail($modelId),
            default => User::query()->findOrFail($modelId),
        };

        // Gleiche Sichtregel wie die Einsatzplan-Seiten (UserPolicy::viewOperationPlan):
        // eigener Plan nur mit "can view own roster", fremde (inkl. Freelancer/
        // Dienstleister) nur mit Dienstplan-Sichtrechten.
        $isOwnPlan = $worker instanceof User && $authUser instanceof User && $authUser->is($worker);
        if (
            !($isOwnPlan && $authUser->can(PermissionEnum::CAN_VIEW_OWN_ROSTER->value))
            && !($authUser instanceof User && UserPolicy::canViewForeignRoster($authUser))
        ) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // Monatsliste (je YYYY-MM)
        $startMonth = $request->get('startMonth');
        $endMonth = $request->get('endMonth');

        $start = $startMonth
            ? Carbon::parse($startMonth . '-01')->startOfMonth()
            : Carbon::now()->startOfMonth();
        $end = $endMonth
            ? Carbon::parse($endMonth . '-01')->endOfMonth()
            : $start->copy()->endOfMonth();

        if ($end->lt($start)) {
            $end = $start->copy()->endOfMonth();
        }

        $months = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $months[] = [
                'start' => $cursor->copy()->startOfMonth(),
                'end' => $cursor->copy()->endOfMonth(),
            ];
            $cursor->addMonth();
        }

        // Globale Grid-Spanne: Montag der ersten KW .. Sonntag der letzten KW
        $gridStart = $months[0]['start']->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = end($months)['end']->copy()->endOfWeek(Carbon::SUNDAY);

        // Eigener Export unterliegt derselben Regel wie der eigene Einsatzplan:
        // nicht festgeschriebene Schichten ggf. ausblenden (Instanz-Setting).
        $hideUncommitted = $type === 'user'
            && $worker instanceof User
            && app(\Artwork\Modules\User\Services\UserService::class)
                ->shouldHideUncommittedShiftsInOwnRoster($worker);

        $daysWithData = $eventService->getDaysWithEventsAndTotalPlannedWorkingHours(
            $modelId,
            $type,
            $gridStart->copy(),
            $gridEnd->copy(),
            $hideUncommitted
        );

        // Verbindliche Projektzuordnungen je Tag (Wünsche bewusst nicht im PDF)
        $assignmentEmployableClass = match ($type) {
            'freelancer' => \Artwork\Modules\Freelancer\Models\Freelancer::class,
            'service_provider', 'serviceProvider' => \Artwork\Modules\ServiceProvider\Models\ServiceProvider::class,
            default => User::class,
        };
        $projectAssignmentsByDate = app(\Artwork\Modules\Project\Services\ProjectDayAssignmentService::class)
            ->getAssignmentsGroupedByDate($assignmentEmployableClass, [$modelId], $gridStart->copy(), $gridEnd->copy())
            ->get($modelId) ?? collect();

        // Feiertage im Zeitraum -> map[Y-m-d] = name
        $holidayMap = [];
        $holidays = Holiday::query()
            ->whereDate('date', '<=', $gridEnd->format('Y-m-d'))
            ->whereDate('end_date', '>=', $gridStart->format('Y-m-d'))
            ->get();
        foreach ($holidays as $holiday) {
            $hStart = Carbon::parse($holiday->date);
            $hEnd = Carbon::parse($holiday->end_date ?? $holiday->date);
            foreach (CarbonPeriod::create($hStart, $hEnd) as $hDay) {
                $holidayMap[$hDay->format('Y-m-d')] = $holiday->name;
            }
        }

        $dayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
        $weeklyWorkingHours = $type === 'user' ? (float) ($worker->weekly_working_hours ?? 0) : null;

        // Schichtqualifikationen (Funktion) -> [id => name]
        $shiftQualifications = \Artwork\Modules\Shift\Models\ShiftQualification::query()
            ->pluck('name', 'id')
            ->all();

        $pages = [];
        foreach ($months as $monthData) {
            $monthStart = $monthData['start'];
            $monthEnd = $monthData['end'];
            $monthNumber = $monthStart->month;

            $weekGridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $weekGridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

            $weeks = [];
            $monthWorkMinutes = 0;
            $daysInMonth = $monthEnd->day;
            $prevHolidayName = null; // Namen mehrtägiger Feiertage/Ferien nur am ersten Tag zeigen

            $cursorWeek = $weekGridStart->copy();
            while ($cursorWeek->lte($weekGridEnd)) {
                $cells = [];
                for ($i = 0; $i < 7; $i++) {
                    $day = $cursorWeek->copy()->addDays($i);
                    $key = $day->format('Y-m-d');
                    $dayData = $daysWithData[$key] ?? null;
                    $inMonth = $day->month === $monthNumber && $day->year === $monthStart->year;
                    $holidayName = $holidayMap[$key] ?? null;
                    $showHolidayLabel = $holidayName !== null && $holidayName !== $prevHolidayName;
                    $prevHolidayName = $holidayName;

                    $shiftBlocks = [];
                    foreach (($dayData['shifts'] ?? []) as $shift) {
                        $shiftBlocks[] = $this->buildUserShiftBlock($shift, $type, $modelId, $shiftQualifications);
                    }

                    $individualBlocks = [];
                    foreach (($dayData['individualTimes'] ?? []) as $it) {
                        $individualBlocks[] = [
                            'title' => $it['title'] ?? '',
                            'full_day' => (bool) ($it['full_day'] ?? false),
                            'start' => !empty($it['start_time']) ? substr((string) $it['start_time'], 0, 5) : null,
                            'end' => !empty($it['end_time']) ? substr((string) $it['end_time'], 0, 5) : null,
                        ];
                    }

                    if ($inMonth && $dayData) {
                        $monthWorkMinutes += $this->hhmmToMinutes($dayData['totalWorkTime'] ?? '00:00');
                    }

                    $projectAssignmentBlocks = collect($projectAssignmentsByDate->get($key) ?? [])
                        ->filter(static fn (array $assignment) => ($assignment['type'] ?? null) === 'binding')
                        ->pluck('project_name')
                        ->unique()
                        ->values()
                        ->all();

                    $cells[] = [
                        'dayNumber' => $day->day,
                        'inMonth' => $inMonth,
                        'isWeekend' => $day->isWeekend(),
                        'isHoliday' => $holidayName !== null,
                        'holidayName' => $showHolidayLabel ? $holidayName : null,
                        'shifts' => $shiftBlocks,
                        'individualTimes' => $individualBlocks,
                        'projectAssignments' => $projectAssignmentBlocks,
                    ];
                }
                $weeks[] = [
                    'kw' => $cursorWeek->isoWeek(),
                    'cells' => $cells,
                ];
                $cursorWeek->addWeek();
            }

            $sollMinutes = $weeklyWorkingHours !== null
                ? (int) round($weeklyWorkingHours / 7 * $daysInMonth * 60)
                : null;
            $diffMinutes = $sollMinutes !== null ? $monthWorkMinutes - $sollMinutes : null;

            $pages[] = [
                'monthLabel' => $monthStart->translatedFormat('F Y'),
                'monthName' => $monthStart->translatedFormat('F'),
                'weekDayNames' => $dayNames,
                'weeks' => $weeks,
                'totalWork' => $this->minutesToHhmm($monthWorkMinutes),
                'sollWork' => $sollMinutes !== null ? $this->minutesToHhmm($sollMinutes) : null,
                'diffWork' => $diffMinutes !== null ? $this->minutesToHhmmSigned($diffMinutes) : null,
                'diffPositive' => $diffMinutes !== null ? $diffMinutes >= 0 : null,
            ];
        }

        // Logo als base64
        $generalSettings = app(\Artwork\Modules\GeneralSettings\Models\GeneralSettings::class);
        $bigLogoBase64 = null;
        if ($generalSettings->big_logo_path) {
            $storage = $this->filesystemManager->disk('public');
            if ($storage->exists($generalSettings->big_logo_path)) {
                $logoContent = $storage->get($generalSettings->big_logo_path);
                $mimeType = $storage->mimeType($generalSettings->big_logo_path);
                $bigLogoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoContent);
            }
        }

        $pdf = $this->snappyPdf->loadView(
            'pdf.user_shift_plan',
            [
                'userName' => $worker->getAttribute('full_name') ?? $worker->getAttribute('name'),
                'showSoll' => $weeklyWorkingHours !== null,
                'pages' => $pages,
                'created_by' => $authUser->first_name . ' ' . $authUser->last_name,
                'created_date' => Carbon::now()->format('d.m.Y'),
                'bigLogoBase64' => $bigLogoBase64,
            ]
        )
            ->setPaper(
                $request->string('paperSize', 'a4'),
                $request->string('paperOrientation', 'landscape')
            )
            ->setOption('dpi', (int) $request->float('dpi', 96));

        $filename = $this->createFilename();

        if ($this->filesystemManager->directoryMissing('pdf')) {
            $this->filesystemManager->makeDirectory('pdf');
        }

        $pdf->save($this->createStoragePath($this->filesystemManager, $filename));

        return $this->inertiaResponseFactory->location(
            $this->urlGenerator->route(
                'calendar.export.pdf.download',
                ['filename' => $filename, 'name' => $this->createDownloadName()]
            )
        );
    }

    /**
     * @param array<string, mixed> $shift
     * @param array<int, string> $shiftQualifications  [shift_qualification_id => name]
     * @return array<string, mixed>
     */
    private function buildUserShiftBlock(array $shift, string $type, int $modelId, array $shiftQualifications): array
    {
        $color = data_get($shift, 'craft.color') ?: '#3730a3';
        $craft = data_get($shift, 'craft.name');
        $room = data_get($shift, 'room.name') ?? data_get($shift, 'event.room.name');
        $project = data_get($shift, 'project.name') ?? data_get($shift, 'event.project.name');
        // Anzeigename des Termins ist `eventName` (wie in der UI), `name` nur Altbestand-Fallback;
        // identisch zum Projektnamen wäre er im PDF eine doppelte Zeile.
        $eventName = data_get($shift, 'event.eventName') ?: data_get($shift, 'event.name');
        if ($eventName !== null && $eventName === $project) {
            $eventName = null;
        }

        $colleagues = [];
        $note = null;
        $function = null;
        foreach (($shift['workers'] ?? []) as $worker) {
            $wType = data_get($worker, 'type');
            $wId = (int) data_get($worker, 'id');

            if ($wType === $type && $wId === $modelId) {
                $note = data_get($worker, 'pivot.short_description') ?: $note;
                $qualId = data_get($worker, 'pivot.shift_qualification_id');
                $function = $qualId ? ($shiftQualifications[$qualId] ?? null) : null;
                continue;
            }

            $name = $this->workerName($worker);
            if ($name !== '') {
                $colleagues[] = $name;
            }
        }

        return [
            'color' => $color,
            'craft' => $craft,
            'function' => $function,
            'start' => !empty($shift['start']) ? substr((string) $shift['start'], 0, 5) : null,
            'end' => !empty($shift['end']) ? substr((string) $shift['end'], 0, 5) : null,
            'room' => $room,
            'project' => $project,
            'event' => $eventName,
            'description' => $shift['description'] ?? null,
            'note' => $note,
            'colleagues' => $colleagues,
            'committed' => (bool) ($shift['is_committed'] ?? false),
        ];
    }

    private function workerName(mixed $worker): string
    {
        $name = trim((string) data_get($worker, 'first_name') . ' ' . (string) data_get($worker, 'last_name'));
        if ($name !== '') {
            return $name;
        }

        return (string) (data_get($worker, 'provider_name') ?? data_get($worker, 'name') ?? '');
    }

    private function hhmmToMinutes(?string $time): int
    {
        if (!$time) {
            return 0;
        }
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    private function minutesToHhmm(int $minutes): string
    {
        $minutes = max(0, $minutes);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function minutesToHhmmSigned(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '+';
        $abs = abs($minutes);

        return sprintf('%s%02d:%02d', $sign, intdiv($abs, 60), $abs % 60);
    }

    public function download(
        Request $request,
        string $filename,
        ResponseFactory $responseFactory,
        FilesystemManager $filesystemManager
    ): BinaryFileResponse {
        return $responseFactory->download(
            $this->createStoragePath($filesystemManager, $filename),
            DownloadFileName::sanitize($request->query('name'))
        )->deleteFileAfterSend();
    }

    private function createStoragePath(FilesystemManager $filesystemManager, string $filename): string
    {
        return $filesystemManager->path('pdf/' . $filename);
    }

    /**
     * The name the file is stored under. The readable name travels separately,
     * see createDownloadName().
     */
    private function createFilename(): string
    {
        return StoredFileName::forGenerated('pdf');
    }

    /**
     * The name the browser saves the file as.
     */
    private function createDownloadName(): string
    {
        return sprintf(
            '%s_%s.pdf',
            Carbon::now()->format('d.m.Y'),
            Str::uuid()
        );
    }

    public function exportDailyViewShiftPlanInProject(Project $project, bool $privacyMode): Response
    {
        /** @var User $user */
        $user = $this->authManager->user();
        $today = Carbon::now()->format('Y-m-d_H:i:s');

        $project->load([
            'shifts.shiftsQualifications', // <- neu (needed-counts)
            'events.event_type',
            'events.room',
            'events.timelines',
            'shifts.craft.qualifications',  // <- neu
            'shifts.room',
            'shifts.users',
            'shifts.freelancer',
            'shifts.serviceProvider',
            'users'
        ]);

        $projectRoles = ProjectRole::query()->pluck('name', 'id')->toArray();

        $groupedUsersByRole = [];

        foreach ($project->users as $projUser) {
            $raw = $projUser->pivot->roles
                ?? $projUser->pivot_roles
                ?? [];

            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }

            $roleIds = collect($raw)
                ->filter(fn ($v) => $v !== null && $v !== '')
                ->map(fn ($v) => (int) $v)
                ->unique()
                ->values()
                ->all();

            if (empty($roleIds)) {
                continue;
            }

            $userPayload = [
                'id'        => $projUser->id,
                'full_name' => $projUser->full_name ?? '',
                'email'     => $projUser->email,
            ];

            foreach ($roleIds as $roleId) {
                $roleName = $projectRoles[$roleId] ?? 'Unbekannt';
                $groupedUsersByRole[$roleName][] = $userPayload;
            }
        }

        $pdfData = $this->dailyShiftPlanPdfBuilder->buildForProject($project, $user, $privacyMode);

        $pdfData['groupedUsersByRole'] = $groupedUsersByRole;

        $pdf = $this->snappyPdf
            ->loadView('pdf.shiftplan_daily_project', $pdfData)
            ->setPaper('a4', 'landscape')
            ->setOption('dpi', 300);

        $safeProjectName = (string) Str::of((string) ($project->name ?? ''))
            ->replace(['/', '\\'], '-')
            ->trim();

        if ($safeProjectName === '') {
            $safeProjectName = 'Projekt';
        }

        // ':' ist zwar nicht der Auslöser dieses Fehlers, kann aber je nach Client/OS problematisch sein.
        $safeToday = str_replace(':', '-', $today);

        return $pdf->download("Shiftplan_{$safeProjectName}_{$safeToday}.pdf");
    }
}
