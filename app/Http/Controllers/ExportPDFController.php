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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportPDFController extends Controller
{
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
    ): Response {
        $startDate = Carbon::parse($request->get('start'));
        $endDate = Carbon::parse($request->get('end'));

        if (($project = $request->get('project'))) {
            if (
                ($firstEventInProject = $projectService->getFirstEventInProject($project)) &&
                ($lastEventInProject = $projectService->getLastEventInProject($project))
            ) {
                $startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
                $endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
            }
        }

        $showCalendar = $calendarService->createCalendarData(
            $startDate,
            $endDate,
            $userService,
            $filterService,
            $filterController,
            $roomService,
            $roomCategoryService,
            $roomAttributeService,
            $eventTypeService,
            $areaService,
            $projectService,
            Auth::user()->calendar_filter,
            null,
            $project
        );

        $pdf = Pdf::loadView('pdf.calendar', [
            'title' => $request->title,
            'rooms' => $roomService->getFilteredRooms(
                $request->input('start'),
                $request->input('end'),
                $userService->getAuthUser()->calendar_filter
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
                ->setProjects(new Collection())
                ->setEventTypes($showCalendar['filterOptions']['eventTypes'])
                ->setRoomCategories($showCalendar['filterOptions']['roomCategories'])
                ->setRoomAttributes($showCalendar['filterOptions']['roomAttributes'])
                ->setEvents(new Collection())
        ])
            ->setPaper($request->input('paperSize'), $request->input('paperOrientation'))
            ->setOptions(['dpi' => $request->input('dpi'), 'defaultFont' => 'sans-serif']);


        $pdfName = Carbon::now()
                ->format('Y-m-d_H-i-s') . '_' . $request
                ->input('paperOrientation') . '_' . str_replace(' ', '_', $request->title) . '_dpi' . $request
                ->input('dpi') . '.pdf';

        if (!Storage::exists('pdf')) {
            Storage::makeDirectory('pdf');
        }

        $pdf->save(storage_path('app/pdf/' . $pdfName));

        return Inertia::location(\route('calendar.export.pdf.download', ['filename' => $pdfName]));
    }

    public function download($filename, ResponseFactory $responseFactory): BinaryFileResponse
    {
        //file is deleted immediately after the request object is populated with pdf content so no cron job to delete
        //old pdfs is required
        return $responseFactory->download(Storage::path('pdf/' . $filename))->deleteFileAfterSend();
    }
}
