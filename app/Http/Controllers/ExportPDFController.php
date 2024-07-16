<?php

namespace App\Http\Controllers;

use Artwork\Modules\Area\Services\AreaService;
use Artwork\Modules\Calendar\Services\CalendarService;
use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\Filter\Services\FilterService;
use Artwork\Modules\Project\Services\ProjectService;
use Artwork\Modules\Room\Http\Resources\RoomPdfResource;
use Artwork\Modules\Room\Models\Room;
use Artwork\Modules\Room\Services\RoomService;
use Artwork\Modules\RoomAttribute\Services\RoomAttributeService;
use Artwork\Modules\RoomCategory\Services\RoomCategoryService;
use Artwork\Modules\User\Services\UserService;
use Barryvdh\DomPDF\PDF;
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
    /**
     * @throws Throwable
     */
    public function createPDF(
        Request $request,
        ProjectService $projectService,
        CalendarService $calendarService,
        RoomService $roomService,
        UserService $userService,
        FilterService $filterService,
        FilterController $filterController,
        RoomCategoryService $roomCategoryService,
        RoomAttributeService $roomAttributeService,
        EventTypeService $eventTypeService,
        AreaService $areaService,
        FilesystemManager $filesystemManager,
        InertiaResponseFactory $inertiaResponseFactory,
        UrlGenerator $urlGenerator,
        PDF $domPdf,
        Carbon $carbon
    ): Response {
        $projectId = $request->get('project');

        $showCalendar = $calendarService->createCalendarData(
            startDate: $projectId ?
                $carbon->create(
                    $projectService->getFirstEventInProject($projectId)->getAttribute('start_time')
                )->startOfDay() :
                $carbon->parse($request->get('start')),
            endDate: $projectId ?
                $carbon->create(
                    $projectService->getLastEventInProject($projectId)->getAttribute('end_time')
                )->endOfDay() :
                $carbon->parse($request->get('end')),
            userService: $userService,
            filterService: $filterService,
            filterController: $filterController,
            roomService: $roomService,
            roomCategoryService: $roomCategoryService,
            roomAttributeService: $roomAttributeService,
            eventTypeService: $eventTypeService,
            areaService: $areaService,
            project: $projectId ? $projectService->findById($projectId) : null,
            calendarFilter: $userService->getAuthUser()->getAttribute('calendar_filter')
        );

        $pdf = $domPdf->loadView(
            'pdf.calendar',
            [
                'title' => $request->get('title'),
                'rooms' => $roomService->getFilteredRooms(
                    $carbon->parse($request->input('start')),
                    $carbon->parse($request->input('end')),
                    $userService->getAuthUser()->getAttribute('calendar_filter')
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
                    ->setAreas($showCalendar['filterOptions']['areas'])
                    ->setEventTypes($showCalendar['filterOptions']['eventTypes'])
                    ->setRoomCategories($showCalendar['filterOptions']['roomCategories'])
                    ->setRoomAttributes($showCalendar['filterOptions']['roomAttributes'])
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
