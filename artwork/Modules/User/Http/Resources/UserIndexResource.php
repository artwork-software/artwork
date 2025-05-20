<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserIndexResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'email' => $this->email,
            'departments' => $this->departments,
            'position' => $this->position,
            'business' => $this->business,
            'phone_number' => $this->phone_number,
            'project_management' => $this->can(PermissionEnum::PROJECT_MANAGEMENT->value),
            'shifts' => $this->getAttribute('shifts')->map(
                function (Shift $shift): array {
                    $event = $shift->getAttribute('event');

                    return [
                        'id' => $shift->getAttribute('id'),
                        'pivotId' => $shift->getRelation('pivot')->getAttribute('id'),
                        'start' => $shift->getAttribute('start'),
                        'end' => $shift->getAttribute('end'),
                        'description' => $shift->getAttribute('description'),
                        'craftAbbreviation' => $shift->getAttribute('craft')->getAttribute('abbreviation'),
                        'days_of_shift' => $shift->getAttribute('days_of_shift'),
                        'roomName' => $event?->getAttribute('room')?->getAttribute('name'),
                        'eventName' => $event?->getAttribute('name') ?? $event?->getAttribute('eventName'),
                        'eventTypeAbbreviation' => $event?->getAttribute('event_type')->getAttribute('abbreviation'),
                    ];
                }
            ),
            'display_name' => $this->getDisplayNameAttribute(),
            'type' => $this->getTypeAttribute(),
            'assigned_craft_ids' => $this->getAssignedCraftIdsAttribute(),
        ];
    }
}
