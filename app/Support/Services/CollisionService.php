<?php

namespace App\Support\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;

class CollisionService
{
    public function __construct()
    {

    }

    /**
     * @param $request
     * @return \App\Builders\EventBuilder
     */
    public function getCollision($request): \App\Builders\EventBuilder
    {
        $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
        $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));

        return Event::query()
            ->whereDate('start_time', '>=', $startDate)
            ->whereDate('end_time', '<=', $endDate)
            ->where('room_id', $request->roomId);
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
}
