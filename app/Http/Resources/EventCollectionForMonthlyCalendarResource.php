<?php

namespace App\Http\Resources;

use App\Models\Event;
use Carbon\CarbonPeriod;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin \App\Models\Event
 */
class EventCollectionForMonthlyCalendarResource extends ResourceCollection
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
        $period = CarbonPeriod::create($request->query('month_start'), $request->query('month_end'));

        return [
            'resource' => class_basename($this),
            'count' => $this->collection->count(),
            'days_in_month' => collect($period)->map(function ($date) {
                $events = $this->collection
                    ->filter(fn (Event $event) => $event->occursAtTime($date))
                    ->map(fn (Event $event) => $event->setAttribute('metaDate', $date));

                $conflicts = $events->filter(fn (Event $event) => $event
                    ->conflictsWithAny($events->where('start_time', '>=', $event->start_time)));

                return [
                    'date_local' => $date->toDateTimeLocalString(),
                    'date' => $date->format('d.m.Y'),
                    'events' => EventCalendarDayResource::collection($events)->resolve(),
                    'conflicts' => $conflicts->pluck('id'),
                ];
            })->toArray(),
        ];
    }
}
