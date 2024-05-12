<?php

namespace Artwork\Modules\Room\Http\Resources;

use Artwork\Modules\Event\Http\Resources\EventShowResource;
use Artwork\Modules\User\Http\Resources\UserWithoutApartmentIndexResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Room
 */
class RoomCalendarResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $events = $this->events()->get();

        $historyArray = [];
        $historyComplete = $this->historyChanges()->all();

        foreach ($historyComplete as $history) {
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
            'room_admins' => UserWithoutApartmentIndexResource::collection($this->users()->wherePivot('is_admin', true)
                ->get())->resolve(),
            'requestable_by' => UserWithoutApartmentIndexResource::collection(
                $this->users()->wherePivot('can_request', true)->get()
            )->resolve(),
            'event_requests' => EventShowResource::collection($events->where('occupancy_option', true))->resolve(),
            'area' => $this->area,
        ];
    }
}
