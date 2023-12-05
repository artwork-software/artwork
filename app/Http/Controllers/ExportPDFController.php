<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventTypPdfResource;
use App\Http\Resources\ResourceModels\CalendarEventCollectionResourceModel;
use App\Http\Resources\RoomPdfResource;
use App\Models\EventType;
use App\Models\Filter;
use App\Models\Project;
use App\Models\Room;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;


class ExportPDFController extends Controller
{


    public function createPDF(Request $request, CalendarController $calendar){
        if(!empty($request->project)){
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
            'dateValue'=>$showCalendar['dateValue'],
            'days' => $showCalendar['days'],
            'selectedDate' => $showCalendar['selectedDate'],
            'filterOptions' => $showCalendar["filterOptions"],
            'personalFilters' => $showCalendar['personalFilters'],
            'eventsWithoutRoom' => $showCalendar['eventsWithoutRoom'],
            'user_filters' => $showCalendar['user_filters'],
            'events' => $events = new CalendarEventCollectionResourceModel(
                areas: $showCalendar['filterOptions']['areas'],
                projects: new Collection(),
                eventTypes: $showCalendar['filterOptions']['eventTypes'],
                roomCategories: $showCalendar['filterOptions']['roomCategories'],
                roomAttributes: $showCalendar['filterOptions']['roomAttributes'],
                events: new Collection(),
                filter: new Collection(),
            ),
        ])->setPaper($request->input('paperSize'), $request->input('paperOrientation'))->setOptions(['dpi' => $request->input('dpi'), 'defaultFont' => 'sans-serif']);
        $pdfName = Carbon::now()->format('Y-m-d_H-i-s') . '-' . $request->input('paperOrientation') .'.pdf';
        $pdf->save(storage_path('app/pdf/' . $pdfName));
        return Storage::download('/pdf/' . $pdfName);
    }


}
