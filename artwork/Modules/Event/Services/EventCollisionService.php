<?php

namespace Artwork\Modules\Event\Services;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\Room\Models\Room;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class EventCollisionService
{
    public function getCollision($request, ?Event $event = null): Builder
    {
        $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
        $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));

        $return = [];
        if (empty($event)) {
            $return = Event::query()
                ->whereBetween('start_time', [$startDate, $endDate])
                ->where('room_id', $request->roomId)
                ->orWhere(function ($query) use ($request, $endDate, $startDate): void {
                    $query->whereBetween('end_time', [$startDate, $endDate])
                        ->where('room_id', $request->roomId);
                })
                ->orWhere(function ($query) use ($request, $endDate, $startDate): void {
                    $query->where('start_time', '>=', $startDate)
                        ->where('end_time', '<=', $endDate)
                        ->where('room_id', $request->roomId);
                })
                ->orWhere(function ($query) use ($request, $endDate, $startDate): void {
                    $query->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate)
                        ->where('room_id', $request->roomId);
                });
        } else {
            $return =  Event::query()
                ->whereNotIn('id', [$event->id])
                ->whereBetween('start_time', [$startDate, $endDate])
                ->where('room_id', $request->roomId)
                ->orWhere(function ($query) use ($event, $request, $endDate, $startDate): void {
                    $query->whereBetween('end_time', [$startDate, $endDate])
                        ->where('room_id', $request->roomId)
                        ->whereNotIn('id', [$event->id]);
                })
                ->orWhere(function ($query) use ($event, $request, $endDate, $startDate): void {
                    $query->where('start_time', '>=', $startDate)
                        ->where('end_time', '<=', $endDate)
                        ->where('room_id', $request->roomId)
                        ->whereNotIn('id', [$event->id]);
                })
                ->orWhere(function ($query) use ($event, $request, $endDate, $startDate): void {
                    $query->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate)
                        ->where('room_id', $request->roomId)
                        ->whereNotIn('id', [$event->id]);
                });
        }
        return $return;
    }

    public function getCollisionCount($request): int
    {
        return $this->getCollision($request)->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConflictEvents($request): array
    {
        $conflictEvents = self::getCollision($request)->get();
        $conflictObj = [];
        foreach ($conflictEvents as $event) {
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

    /**
     * @return Event[]
     */
    public function adjoiningCollision($request): array
    {
        $startDate = Carbon::parse($request->start)->setTimezone(config('app.timezone'));
        $endDate = Carbon::parse($request->end)->setTimezone(config('app.timezone'));

        $joiningRooms = Room::find($request->roomId)->adjoining_rooms()->get();
        $events = [];
        foreach ($joiningRooms as $joiningRoom) {
            $events[] = Event::query()
                ->whereDate('start_time', '>=', $startDate)
                ->whereDate('end_time', '<=', $endDate)
                ->where('room_id', $joiningRoom->id)
                ->get();
        }
        return $events;
    }
}
