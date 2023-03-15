<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class CalenderController extends Controller
{

    public function createCalenderData(){

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


        $calenderPeriod = CarbonPeriod::create($startDate, $endDate);
        $returnArray = [];
        $rooms = Room::all();

        foreach ($rooms as $room){
            foreach ($calenderPeriod as $period){
                $returnArray[$room->id][$period->format('d.m.')] = Event::where('room_id', $room->id)->whereBetween('start_time', [$period->startOfDay()->format('Y-m-d H:i:s'), $period->endOfDay()->format('Y-m-d H:i:s')])->get();
            }
        }



        return $returnArray;
    }
}
