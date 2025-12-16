<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Models\Area;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\EventCalendarService;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\Event\Models\EventProperty;
use Artwork\Modules\EventType\Models\EventType;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Http\Resources\RoomPdfResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Models\RoomAttribute;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserFilter;
use Artwork\Modules\User\Services\UserService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Auth\AuthManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\ResponseFactory as InertiaResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        protected PDF $domPdf,
        protected AuthManager $authManager,
        protected EventCalendarService $eventCalendarService,
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
            $this->eventCalendarService->filterRoomsEvents(
                $rooms,
                $userCalendarFilter,
                $startDate,
                $endDate,
                $userCalendarSettings
            ),
            $startDate,
            $endDate
        );

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
                $perEventHeight = 18; // px, entspricht ungefähr der Mindesthöhe eines Event-Bubbles inkl. Margin
                $baseMinHeight  = 36; // px, Mindesthöhe wenn keine Events vorhanden sind

                // Liste der Tag-Strings (Format wie im View: d.m.Y)
                $allDayStrings = array_map(static fn ($d) => $d['fullDay'], $days);

                // Kalender-Räume-Struktur aus DTO
                $calendarRooms = $calendar->rooms ?? [];

                // Hilfs-Lookup: roomId -> calendar room block
                $calendarRoomLookup = [];
                foreach ($calendarRooms as $roomBlock) {
                    $rid = $roomBlock['roomId'] ?? null;
                    if ($rid !== null) {
                        $calendarRoomLookup[$rid] = $roomBlock;
                    }
                }

                foreach ($rooms as $room) {
                    $rid = $room->id;
                    $maxPerSlot = [
                        'morning' => 0,
                        'noon'    => 0,
                        'evening' => 0,
                    ];

                    $roomBlock = $calendarRoomLookup[$rid] ?? null;
                    if (!$roomBlock) {
                        // Falls keine Inhalte vorhanden sind, auf Basis-Mindesthöhe setzen
                        $rowHeights[$rid] = [
                            'morning' => $baseMinHeight,
                            'noon'    => $baseMinHeight,
                            'evening' => $baseMinHeight,
                        ];
                        continue;
                    }

                    foreach ($allDayStrings as $dayDisplay) {
                        $events = $roomBlock['content'][$dayDisplay]['events'] ?? [];
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
            // New export mode: fixed segment height
            $baseSegmentHeight = 36; // px (gerne anpassen, wenn dir die Zellen zu "flach" wirken)

            foreach ($rooms as $room) {
                $rowHeights[$room->id] = [
                    'morning' => $baseSegmentHeight,
                    'noon'    => $baseSegmentHeight,
                    'evening' => $baseSegmentHeight,
                ];
            }
        }

        // Select blade template based on export mode
        $bladeTemplate = $exportMode === 'block' ? 'pdf.calendarExportNotRelative' : 'pdf.calendar';

        // PDF rendern
        $pdf = $this->domPdf->loadView(
            $bladeTemplate,
            [
                'title'          => $request->get('title') ?? 'Raumbelegung',
                'project'        => $project,
                'user_filters'   => $userCalendarFilter,
                'created_by'     => $user->full_name,
                'calendar'       => $calendar,     // CalendarFrontendDataDTO
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
            ]
        )
            ->setPaper(
                $request->string('paperSize'),
                $request->string('paperOrientation')
            )
            ->setOptions([
                'dpi'         => $request->float('dpi'),
                'defaultFont' => 'sans-serif'
            ]);

        $filename = $this->createFilename(
            $request->string('paperOrientation', ''),
            $request->string('title', ''),
            $request->float('dpi', '')
        );

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
        // Ganztägig -> in allen Slots anzeigen
        if (!empty($event->allDay)) {
            return true;
        }

        if (empty($event->start) || empty($event->end)) {
            return false;
        }

        $eventStart = \Carbon\Carbon::parse($event->start);
        $eventEnd   = \Carbon\Carbon::parse($event->end);

        $day = \Carbon\Carbon::createFromFormat('d.m.Y', $dayDisplay);

        switch ($slot) {
            case 'morning': // 00:00 - 12:00
                $slotStart = $day->copy()->startOfDay();           // 00:00
                $slotEnd   = $day->copy()->setTime(12, 0, 0);      // 12:00
                break;

            case 'noon': // 12:00 - 18:00
                $slotStart = $day->copy()->setTime(12, 0, 0);      // 12:00
                $slotEnd   = $day->copy()->setTime(18, 0, 0);      // 18:00
                break;

            case 'evening': // 18:00 - 24:00
            default:
                $slotStart = $day->copy()->setTime(18, 0, 0);      // 18:00
                $slotEnd   = $day->copy()->endOfDay()->setTime(23, 59, 59); // 23:59
                break;
        }

        // Overlap wenn: Start < SlotEnd && Ende > SlotStart
        return $eventStart < $slotEnd && $eventEnd > $slotStart;
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

    private function createFilename(
        string $paperOrientation,
        string $title,
        string $dpi
    ): string {
        return sprintf(
            '%s_%s_%s_dpi_%s.pdf',
            Carbon::now()->format('d.m.Y-H:i:s'),
            $paperOrientation,
            str_replace(' ', '_', $title),
            $dpi
        );
    }
}
