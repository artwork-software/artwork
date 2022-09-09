<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Room
 */
class RoomIndexWithoutEventsResource extends JsonResource
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
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'temporary' => (bool) $this->temporary,
            'start_date' => $this->start_date?->format('d.m.Y'),
            'end_date' => $this->end_date?->format('d.m.Y'),
            'created_at' => $this->created_at->format('d.m.Y, H:i'),
            'created_by' => User::where('id', $this->user_id)->first(),
            'room_admins' => UserIndexResource::collection($this->room_admins)->resolve(),
        ];
    }
}
