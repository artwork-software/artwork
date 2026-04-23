<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Calendar\Services\ShiftCalendarService;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Project\Models\ProjectRole;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Shift\Services\DailyShiftPlanPdfBuilder;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Services\UserService;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Auth\AuthManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\UrlGenerator;
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


        $userCalendarSettings = $user->getAttribute('calendar_settings');
        $filterData   = $request->filter;

        $userCalendarFilter = new UserFilter($filterData);
        $userCalendarFilter->exists = false;
        //dd($userCalendarFilter);

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
                $userCalendarSettings
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
            // New export mode: dynamic segment height so every event's time-proportional
            // pixel area is large enough to display its full text (name + time).
            $SLOT_MINUTES      = 360; // each slot spans 6 hours
            $baseSegmentHeight = 52;  // px minimum per slot
            $maxSegmentHeight  = 400; // px cap to prevent absurdly tall rows
            $baseCharsPerLine  = 14;  // chars per line at full column width (single lane)
            $lineHeight        = 11;
            $paddingPx         = 8;

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

                        // Count max concurrent events per slot to determine lane count
                        $slotCounts = ['morning' => 0, 'noon' => 0, 'evening' => 0];
                        foreach ($events as $event) {
                            foreach (['morning', 'noon', 'evening'] as $slot) {
                                if (self::eventOverlapsSlot($event, $dayDisplay, $slot)) {
                                    $slotCounts[$slot]++;
                                }
                            }
                        }
                        $laneCount = max(1, max($slotCounts['morning'], $slotCounts['noon'], $slotCounts['evening']));

                        // Fewer chars per line when events share lanes (narrower)
                        $effectiveCharsPerLine = max(4, (int) floor($baseCharsPerLine / $laneCount));

                        $tz = config('app.timezone');
                        foreach ($events as $event) {
                            $start    = \Illuminate\Support\Carbon::parse($event->start)->timezone($tz);
                            $end      = \Illuminate\Support\Carbon::parse($event->end)->timezone($tz);
                            $startMin = max(360, min(1440, ((int) $start->format('H')) * 60 + ((int) $start->format('i'))));
                            $endMin   = max(360, min(1440, ((int) $end->format('H')) * 60 + ((int) $end->format('i'))));
                            $allDay   = (bool) ($event->allDay ?? false);

                            $slot = $startMin < 720 ? 'morning' : ($startMin < 1080 ? 'noon' : 'evening');

                            // Calculate content height needed for text
                            $name        = $event->eventName ?? '';
                            $abbr        = $event->eventType?->abbreviation ?? '';
                            $projectName = $event->project->name ?? '';

                            $titleText    = ($abbr !== '' ? $abbr . ': ' : '') . $name;
                            $titleLines   = max(1, (int) ceil(mb_strlen($titleText) / $effectiveCharsPerLine));
                            $projectLines = $projectName !== '' ? max(1, (int) ceil(mb_strlen($projectName) / $effectiveCharsPerLine)) : 0;
                            $contentHeight = ($titleLines + $projectLines + 1) * $lineHeight + $paddingPx;

                            // How much of the slot does this event occupy? (quantized to hours)
                            if ($allDay) {
                                $durationFraction = 1.0;
                            } else {
                                // Quantize to full hours like the Blade template does
                                $qStart  = (int) (floor($startMin / 60) * 60);
                                $qEnd    = (int) (ceil($endMin / 60) * 60);
                                $qEnd    = max($qEnd, $qStart + 60); // minimum 1 hour
                                $eventMinutesInSlot = min($qEnd - $qStart, $SLOT_MINUTES);
                                $durationFraction   = max(0.1, $eventMinutesInSlot / $SLOT_MINUTES);
                            }

                            // Slot must be tall enough so that this event's proportional slice fits its content
                            $requiredSlotHeight = (int) ceil($contentHeight / $durationFraction);
                            $requiredSlotHeight = min($requiredSlotHeight, $maxSegmentHeight);

                            if ($requiredSlotHeight > $slotMaxHeight[$slot]) {
                                $slotMaxHeight[$slot] = $requiredSlotHeight;
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
                'colorSource'    => $request->get('colorSource', 'eventType'),
                'paperSize'      => $request->string('paperSize', 'a4'),
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
            $this->urlGenerator->route('calendar.export.pdf.download', ['filename' => $filename])
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


    public function createMonthlyPDF(Request $request): Response
    {
        /** @var User $user */
        $user = $this->authManager->guard()->user();
        $userFilter = $user->userFilters()->calendarFilter()->first();

        $projectId = $request->get('project');
        $userCalendarSettings = $user->getAttribute('calendar_settings');
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
            $startMonth = $request->get('startMonth');
            $endMonth = $request->get('endMonth');

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
                $userCalendarSettings
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
                'colorSource' => $request->get('colorSource', 'eventType'),
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
            $this->urlGenerator->route('calendar.export.pdf.download', ['filename' => $filename])
        );
    }

    public function download(
        string $filename,
        ResponseFactory $responseFactory,
        FilesystemManager $filesystemManager
    ): BinaryFileResponse {
        return $responseFactory->download(
            $this->createStoragePath($filesystemManager, $filename)
        )->deleteFileAfterSend();
    }

    private function createStoragePath(FilesystemManager $filesystemManager, string $filename): string
    {
        return $filesystemManager->path('pdf/' . $filename);
    }

    private function createFilename(): string {
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
