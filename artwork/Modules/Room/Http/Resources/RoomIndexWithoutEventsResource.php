<?php

namespace Artwork\Modules\Room\Http\Resources;

use Artwork\Modules\User\Http\Resources\UserIndexResource;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Room
 */
class RoomIndexWithoutEventsResource extends JsonResource
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
            'description' => $this->description,
            'temporary' => (bool) $this->temporary,
            'start_date' => $this->start_date?->format('d.m.Y'),
            'end_date' => $this->end_date?->format('d.m.Y'),
            'created_at' => $this->created_at?->format('d.m.Y, H:i'),
            'created_by' => User::where('id', $this->user_id)->first(),
            'room_admins' => UserIndexResource::collection(
                $this->users()->wherePivot('is_admin', true)->get()
            )->resolve(),
        ];
    }
}
