<?php

namespace App\Support\Services;

use App\Models\Event;
use App\Models\Room;
use Barryvdh\Debugbar\Facades\Debugbar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class CollisionService
{
    public function __construct()
    {

    }

    /**
     * @param $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getCollision($request, ?Event $event = null): \Illuminate\Database\Eloquent\Builder
    {
        $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
        $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));

        $return = [];
        if(empty($event)){
            $return = Event::query()
                ->whereBetween('start_time', [$startDate, $endDate])
                ->where('room_id', $request->roomId)
                ->orWhere(function($query) use ($request, $endDate, $startDate) {
                    $query->whereBetween('end_time', [$startDate, $endDate])
                        ->where('room_id', $request->roomId);
                })
                ->orWhere(function($query) use ($request, $endDate, $startDate) {
                    $query->where('start_time', '>=', $startDate)
                        ->where('end_time', '<=', $endDate)
                        ->where('room_id', $request->roomId);
                })
                ->orWhere(function($query) use ($request, $endDate, $startDate) {
                    $query->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate)
                        ->where('room_id', $request->roomId);
                });
        } else {
            $return =  Event::query()
                ->whereNotIn('id', [$event->id])
                ->whereBetween('start_time', [$startDate, $endDate])
                ->where('room_id', $request->roomId)
                ->orWhere(function($query) use ($event, $request, $endDate, $startDate) {
                    $query->whereBetween('end_time', [$startDate, $endDate])
                        ->where('room_id', $request->roomId)
                        ->whereNotIn('id', [$event->id]);
                })
                ->orWhere(function($query) use ($event, $request, $endDate, $startDate) {
                    $query->where('start_time', '>=', $startDate)
                        ->where('end_time', '<=', $endDate)
                        ->where('room_id', $request->roomId)
                        ->whereNotIn('id', [$event->id]);
                })
                ->orWhere(function($query) use ($event, $request, $endDate, $startDate) {
                    $query->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate)
                        ->where('room_id', $request->roomId)
                        ->whereNotIn('id', [$event->id]);
                });
        }
        return $return;

    }


    /**
     * @param $request
     * @return int
     */
    public function getCollisionCount($request): int
    {
        return $this->getCollision($request)->count();
    }

    /**
     * @param $request
     * @return array
     */
    public function getConflictEvents($request): array
    {
        $conflictEvents = self::getCollision($request)->get();
        $conflictObj = [];
        foreach ($conflictEvents as $event){
            $conflictObj[] = [
                'id' => $event->id,
                'title' => $event->eventName,
                'event' => $event,
                'created_by' => $event->creator,
                'created_at' => $event->created_at
            ];
        }
        return $conflictObj;
    }

    public function adjoiningCollision($request): array
    {
        $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
        $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));

        $joiningRooms = Room::find($request->roomId)->adjoining_rooms()->get();
        $events = [];
        foreach ($joiningRooms as $joiningRoom){
            $events[] = Event::query()
                ->whereDate('start_time', '>=', $startDate)
                ->whereDate('end_time', '<=', $endDate)
                ->where('room_id', $joiningRoom->id)->get();
        }
        return $events;
    }
}
