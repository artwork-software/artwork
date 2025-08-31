<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarDataService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Http\Resources\RoomPdfResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\Room\Services\RoomAttributeService;
use Artwork\Modules\Room\Services\RoomCategoryService;
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
        private readonly CalendarDataService $calendarDataService
    ) {
    }
    /**
     * @throws Throwable
     */
    public function createPDF(
        Request $request,
        ProjectService $projectService,
        RoomService $roomService,
        UserService $userService,
        FilesystemManager $filesystemManager,
        InertiaResponseFactory $inertiaResponseFactory,
        UrlGenerator $urlGenerator,
        PDF $domPdf,
        Carbon $carbon
    ): Response {
        $projectId = $request->get('project');
        $startDate = $request->get('start');
        $endDate = $request->get('end');
        $userCalendarFilter = $userService->getAuthUser()->userFilters()->calendarFilter()->first();

        if (!$startDate && $projectId) {
            $startDate = $carbon->create($projectService->getFirstEventInProject($projectId)
                ->getAttribute('start_time'))->startOfDay();
        }

        if (!$endDate && $projectId) {
            $endDate = $carbon->create(
                $projectService->getLastEventInProject($projectId)->getAttribute('end_time')
            )->endOfDay();
        }

        if (!$startDate && !$endDate && !$projectId) {
            if ($userCalendarFilter->start_date && $userCalendarFilter->end_date) {
                $startDate = $carbon->create($userCalendarFilter->start_date)->startOfDay();
                $endDate = $carbon->create($userCalendarFilter->end_date)->endOfDay();
            } else {
                $startDate = Carbon::now()->startOfMonth()->startOfDay();
                $endDate = Carbon::now()->endOfMonth()->endOfDay();
            }
        }

        if ($request->get('start') && $request->get('end') && $projectId) {
            $startDate = $carbon->create($request->get('start'))->startOfDay();
            $endDate = $carbon->create($request->get('end'))->endOfDay();
        }

        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate = Carbon::parse($endDate)->endOfDay();

        $showCalendar = $this->calendarDataService->createCalendarData(
            startDate: $startDate,
            endDate: $endDate,
            calendarFilter: $userService->getAuthUser()->userFilters()->calendarFilter()->first(),
            project: $projectId ? $projectService->findById($projectId) : null
        );

        $pdf = $domPdf->loadView(
            'pdf.calendar',
            [
                'title' => $request->get('title'),
                'rooms' => $roomService->getFilteredRooms(
                    $carbon->parse($request->input('start')),
                    $carbon->parse($request->input('end')),
                    $userService->getAuthUser()->userFilters()->calendarFilter()->first()
                ),
                'filterRooms' => RoomPdfResource::collection(Room::all()),
                'calendar' => $showCalendar['roomsWithEvents'],
                'dateValue' => $showCalendar['dateValue'],
                'days' => $showCalendar['days'],
                'selectedDate' => $showCalendar['selectedDate'],
                'filterOptions' => $showCalendar["filterOptions"],
                'personalFilters' => $showCalendar['personalFilters'],
                'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
                'user_filters' => $showCalendar['user_filters'],
                'events' => CalendarEventDto::newInstance()
                    ->setAreas($showCalendar['filterOptions']['area_ids'])
                    ->setEventTypes($showCalendar['filterOptions']['event_type_ids'])
                    ->setRoomCategories($showCalendar['filterOptions']['room_attribute_ids'])
                    ->setRoomAttributes($showCalendar['filterOptions']['room_attribute_ids'])
                    ->setProjects(new Collection())
                    ->setEvents(new Collection())
            ]
        )->setPaper(
            $request->string('paperSize'),
            $request->string('paperOrientation')
        )->setOptions(
            [
                'dpi' => $request->float('dpi'),
                'defaultFont' => 'sans-serif'
            ]
        );

        $filename = $this->createFilename(
            $carbon,
            $request->string('paperOrientation', ''),
            $request->string('title', ''),
            $request->float('dpi', '')
        );

        if ($filesystemManager->directoryMissing('pdf')) {
            $filesystemManager->makeDirectory('pdf');
        }

        $pdf->save($this->createStoragePath($filesystemManager, $filename));

        return $inertiaResponseFactory->location(
            $urlGenerator->route('calendar.export.pdf.download', ['filename' => $filename])
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

    private function createFilename(
        Carbon $carbon,
        string $paperOrientation,
        string $title,
        string $dpi
    ): string {
        return sprintf(
            '%s_%s_%s_dpi_%s.pdf',
            $carbon->now()->format('d.m.Y-H:i:s'),
            $paperOrientation,
            str_replace(' ', '_', $title),
            $dpi
        );
    }
}
