<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EventCollectionForDailyCalendarResource extends ResourceCollection
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $today = Carbon::parse($request->query('wanted_day'));

        $events = $this->collection
            ->filter(fn (Event $event) => $event->occursAtTime($today))
            ->map(fn (Event $event) => $event->setAttribute('metaDate', $today));

        return [
            'resource' => class_basename($this),
            'count' => $this->collection->count(),
            'events' => EventCalendarDayResource::collection($events)->resolve(),
        ];
    }
}
