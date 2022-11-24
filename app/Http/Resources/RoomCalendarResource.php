<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \App\Models\Room
 */
class RoomCalendarResource extends JsonResource
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
        $requestedDay = Carbon::parse($request->query('wanted_day'));

        /** @var \Illuminate\Database\Eloquent\Collection $events */
        $events = $this->events()
            /** @see \App\Builders\EventBuilder::visibleForUser() */
            ->visibleForUser(Auth::user())
            ->occursAt($requestedDay)
            ->get();

        $historyArray = [];
        $historyComplete = $this->historyChanges()->all();

        foreach ($historyComplete as $history){
            $historyArray[] = [
                'changes' => json_decode($history->changes),
                'created_at' => $history->created_at->diffInHours() < 24
                    ? $history->created_at->diffForHumans()
                    : $history->created_at->format('d.m.Y, H:i'),
            ];
        }

        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'temporary' => $this->temporary,
            'room_history' => $historyArray,
            'created_by' => $this->creator,
            'created_at' => $this->created_at?->format('d.m.Y'),
            'start_date' => $this->start_date?->format('d.m.Y'),
            'start_date_dt_local' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->format('d.m.Y'),
            'end_date_dt_local' => $this->end_date?->toDateString(),
            'room_files' => $this->room_files,
            'area_id' => $this->area_id,
            'everyone_can_book' => $this->everyone_can_book,
            'room_admins' => UserWithoutApartmentIndexResource::collection($this->room_admins)->resolve(),
            'event_requests' => EventShowResource::collection($events->where('occupancy_option', true))->resolve(),
            'area' => $this->area,
        ];
    }
}
