<?php

namespace Artwork\Modules\Room\Http\Resources;

use Artwork\Modules\User\Http\Resources\UserIconResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Room\Models\Room
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
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'color' => $this->color,
            'description' => $this->description,
            'temporary' => $this->temporary,
            'created_by' => $this->creator,
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'everyone_can_book' => $this->everyone_can_book,
            'relevant_for_disposition' => $this->relevant_for_disposition,
            'capacity' => $this->capacity,
            // ohne Datum null statt "heute" (Carbon::parse(null)) — sonst schrieb das Bearbeiten-Modal
            // jedem Raum das aktuelle Datum als Zeitraum zurück
            'start_date' => $this->start_date?->format('d.m.Y'),
            'start_date_dt_local' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->format('d.m.Y'),
            'end_date_dt_local' => $this->end_date?->toDateString(),
            // Relationen statt frischer Queries: admins/creator kommen über Room::$with,
            // categories/attributes/adjoining_rooms laden die Aufrufer eager (AreaController)
            // — sonst vier Queries je Raum.
            'room_admins' => UserIconResource::collection($this->admins)->resolve(),
            'room_categories' => $this->categories,
            'room_attributes' => $this->attributes,
            'adjoining_rooms' => $this->adjoining_rooms
        ];
    }
}
