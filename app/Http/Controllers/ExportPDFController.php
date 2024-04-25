<?php

namespace App\Http\Controllers;

use Artwork\Modules\Event\DTOs\CalendarEventDto;
use Artwork\Modules\Filter\Models\Filter;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\Room\Http\Resources\RoomPdfResource;
use Artwork\Modules\Room\Models\Room;
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
    public function createPDF(Request $request, CalendarController $calendar): Response
    {
        if (!empty($request->project)) {
            $project = Project::find($request->project);
        } else {
            $project = null;
        }

        $showCalendar = $calendar->createCalendarData(
            project: $project,
            startDate: $request->input('start'),
            endDate: $request->input('end'),
        );

        $pdf = Pdf::loadView('pdf.calendar', [
            'title' => $request->title,
            'rooms' => $calendar->filterRooms($request->input('start'), $request->input('end'))->get(),
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
                ->setFilter(new Collection())
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

        // Rückgabe des Pfads zur heruntergeladenen Datei statt direktem Download
        $downloadUrl = Storage::url('pdf/' . $pdfName);

        return Inertia::location(\route('calendar.export.pdf.download', ['filename' => $pdfName]));
    }

    public function download($filename, ResponseFactory $responseFactory): BinaryFileResponse
    {
        //file is deleted immediately after the request object is populated with pdf content so no cron job to delete
        //old pdfs is required
        return $responseFactory->download(Storage::path('pdf/' . $filename))->deleteFileAfterSend();
    }
}
