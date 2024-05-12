<?php

namespace Artwork\Modules\Event\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class EventShowResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->eventName,
            'description' => $this->description,
            'start_time' => Carbon::parse($this->start_time)->format('d.m.Y, H:i'),
            'startDate' => Carbon::parse($this->start_time)->format('Y-m-d'),
            'startTime' => Carbon::parse($this->start_time)->format('H:i'),
            'start_time_weekday' => Carbon::parse($this->start_time)->format('l'),
            'end_time' => Carbon::parse($this->end_time)->format('d.m.Y, H:i'),
            'endDate' => Carbon::parse($this->end_time)->format('Y-m-d'),
            'endTime' => Carbon::parse($this->end_time)->format('H:i'),
            'end_time_weekday' => Carbon::parse($this->end_time)->format('l'),
            'start_time_dt_local' => Carbon::parse($this->start_time)->toDateTimeLocalString(),
            'end_time_dt_local' => Carbon::parse($this->end_time)->toDateTimeLocalString(),
            'occupancy_option' => $this->occupancy_option,
            'audience' => $this->audience,
            'is_loud' => $this->is_loud,
            'event_type' => $this->event_type,
            'room' => $this->room,
            'project' => $this->project,
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'created_by' => $this->creator,
        ];
    }
}
