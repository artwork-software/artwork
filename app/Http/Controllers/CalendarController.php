<?php

namespace App\Http\Controllers;

use App\Http\Resources\CalendarEventResource;
use App\Models\Event;
use App\Models\Project;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    protected ?Carbon $startDate = null;
    protected ?Carbon $endDate = null;

    public function __construct()
    {
    }

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

    public function createCalendarData($type='', ?Project $project = null){

        $calendarType = '';
        $selectedDate = null;
        $this->startDate = Carbon::now()->startOfDay();

        if($type === 'dashboard'){
            $this->endDate = Carbon::now()->endOfDay();
        }else{
            $this->endDate = Carbon::now()->addWeeks()->endOfDay();
        }
        if(!empty($project)){
            $firstEventInProject = $project->events()->orderBy('start_time', 'ASC')->first();
            $lastEventInProject = $project->events()->orderBy('end_time', 'DESC')->first();
            if(!empty($firstEventInProject) && !empty($lastEventInProject)){
                $this->startDate = Carbon::create($firstEventInProject->start_time)->startOfDay();
                $this->endDate = Carbon::create($lastEventInProject->end_time)->endOfDay();
            } else {
                $this->setDefaultDates();
            }

        } else {
            $this->setDefaultDates();
        }


        if($this->endDate && $this->startDate){
            if(\request('startDate') !== \request('endDate')){
                $calendarType = 'individual';
            }else{
                $calendarType = 'daily';
                $selectedDate = $this->startDate->format('Y-m-d');
            }
        }

        $calendarPeriod = CarbonPeriod::create($this->startDate, $this->endDate);
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
            'dateValue' => [$this->startDate->format('Y-m-d'),$this->endDate->format('Y-m-d')],
            // only used for dashboard -> default Dashboard should show Vuecal-Daily calendar with current day
            'calendarType' => $calendarType,
            // Selected Date is needed for change from individual Calendar to VueCal-Daily, so that vuecal knows which date to load
            'selectedDate' => $selectedDate,
            'roomsWithEvents' => $better
        ];
    }

    private function setDefaultDates(){
        if(\request('startDate')){
            $this->startDate = Carbon::create(\request('startDate'))->startOfDay();
        }
        if(\request('endDate')){
            $this->endDate = Carbon::create(\request('endDate'))->endOfDay();
        }
    }
}
