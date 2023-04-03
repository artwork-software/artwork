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

    public function createCalendarData($type=''){

        $startDate = Carbon::now()->startOfDay();
        if($type === 'dashboard'){
            $endDate = Carbon::now()->endOfDay();
        }else{
            $endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        $calendarType = '';

        if(\request('startDate')){
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
        }

        if(\request('endDate')){
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        }

        if($endDate && $startDate){
            if(\request('startDate') !== \request('endDate')){
                $calendarType = 'individual';
            }else{
                $calendarType = 'daily';
            }
        }

        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        $periodArray = [];
        $rooms = Room::all();

        foreach ($calendarPeriod as $period) {
            $periodArray[] = $period->format('d.m.');
        }

        $better = Room::with(['events.room', 'events.project', 'events.creator'])
            ->get()
            ->map(fn($room) => collect($calendarPeriod)
                ->mapWithKeys(fn($date) => [
                    $date->format('d.m.') => CalendarEventResource::collection($this->get_events_of_day($date, $room))
                ]));

        return [
            'days' => $periodArray,
            'dateValue' => [$startDate->format('Y-m-d'),$endDate->format('Y-m-d')],
            'calendarType' => $calendarType,
            'roomsWithEvents' => $better
        ];
    }
}
