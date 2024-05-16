<?php

namespace Artwork\Modules\Room\Http\Resources;

use Artwork\Modules\Event\Http\Resources\EventShowResource;
use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\User\Http\Resources\UserIconResource;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Room
 */
class RoomIndexResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $startTime = Carbon::parse($request->get('start_time'));
        $endTime = Carbon::parse($request->get('start_time'));

        $startTimeEvents = $this->events->filter(fn (Event $event) => $event->occursAtTime($startTime));
        $endTimeEvents = $this->events->filter(fn (Event $event) => $event->occursAtTime($endTime));

        return [
            'resource' => class_basename($this),
            'conflicts_start_time' => EventShowResource::collection($startTimeEvents)->resolve(),
            'conflicts_end_time' => EventShowResource::collection($endTimeEvents)->resolve(),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'temporary' => $this->temporary,
            'created_by' => $this->creator,
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'everyone_can_book' => $this->everyone_can_book,
            'start_date' => Carbon::parse($this->start_date)->format('d.m.Y'),
            'start_date_dt_local' => Carbon::parse($this->start_date)->toDateString(),
            'end_date' => Carbon::parse($this->end_date)->format('d.m.Y'),
            'end_date_dt_local' => Carbon::parse($this->end_date)->toDateString(),
            'room_admins' => UserIconResource::collection($this->users()->wherePivot('is_admin', true)->get())
                ->resolve(),
            'room_categories' => $this->categories()->get(),
            'room_attributes' => $this->attributes,
            'adjoining_rooms' => $this->adjoining_rooms()->get()
        ];
    }
}
