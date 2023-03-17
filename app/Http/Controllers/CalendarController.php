<?php

namespace App\Http\Controllers;

use App\Http\Resources\CalendarEventResource;
use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class CalendarController extends Controller
{

    private function get_events_of_day($date_of_day, $room): array
    {

        $eventsToday = [];
        $today = $date_of_day->format('d.m.');

        foreach ($room->events as $event) {
            if(in_array($today, $event->days_of_event)) {
                $eventsToday[] = $event;
            }
        }

        return $eventsToday;
    }

    public function createCalendarData(){

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addWeeks()->endOfDay();

        if(\request('startDate')){
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
        }

        if(\request('endDate')){
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        }

        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        //$returnArray = [];
        $periodArray = [];
        $rooms = Room::all();

        foreach ($calendarPeriod as $period) {
            $periodArray[] = $period->format('d.m.');
        }
//        foreach ($rooms as $room){
//            foreach ($calendarPeriod as $period){
//                $returnArray[$room->id][$period->format('d.m.')] = CalendarEventResource::collection(Event::where('room_id', $room->id)
//                    ->whereBetween('start_time', [$period->startOfDay()->format('Y-m-d H:i:s'), $period->endOfDay()->format('Y-m-d H:i:s')])
//                    ->orWhere(function($query) use ($room, $period) {
//                        $query->whereBetween('end_time', [$period->startOfDay()->format('Y-m-d H:i:s'), $period->endOfDay()->format('Y-m-d H:i:s')])
//                        ->where('room_id', $room->id);
//                    })
//                    ->get());
//            }
//        }

        $better = Room::with(['events.room', 'events.project', 'events.creator'])
            ->get()
            ->map(fn($room) => collect($calendarPeriod)
                ->mapWithKeys(fn($date) => [
                    $date->format('d.m.') => CalendarEventResource::collection($this->get_events_of_day($date, $room))
                ]));

        return [
            'days' => $periodArray,
            'roomsWithEvents' => $better
        ];
    }
}
