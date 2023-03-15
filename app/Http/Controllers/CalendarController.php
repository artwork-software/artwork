<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class CalendarController extends Controller
{

    public function createCalendarData(){

        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addWeeks(1)->endOfDay();

        if(\request('startDate')){
            $startDate = Carbon::create(\request('startDate'))->startOfDay();
        }

        if(\request('endDate')){
            $endDate = Carbon::create(\request('endDate'))->endOfDay();
        }

        /*$events = Event::query()
            ->whereBetween('start_time', [$startDate, $endDate])
            ->orWhere(function($query) use ($endDate, $startDate) {
                $query->whereBetween('end_time', [$startDate, $endDate]);
            })->with(['room'])->get();*/


        $calendarPeriod = CarbonPeriod::create($startDate, $endDate);
        $returnArray = [];
        $periodArray = [];
        $rooms = Room::all();

        foreach ($calendarPeriod as $period) {
            $periodArray[] = $period->format('d.m.');
        }
        foreach ($rooms as $room){
            foreach ($calendarPeriod as $period){
                $returnArray[$room->id][$period->format('d.m.')] = Event::where('room_id', $room->id)->whereBetween('start_time', [$period->startOfDay()->format('Y-m-d H:i:s'), $period->endOfDay()->format('Y-m-d H:i:s')])->get();
            }
        }



        return [$periodArray, $returnArray];
    }
}
