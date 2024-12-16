<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserWithoutApartmentIndexResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $pivot = $this->getRelation('pivot');

        $user = [
            'resource' => class_basename($this),
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'profile_photo_url' => $this->profile_photo_url,
            'email' => $this->email,
            'description' => $this->description,
            'position' => $this->position,
            'business' => $this->business,
            'phone_number' => $this->phone_number,
        ];

        if ($pivot) {
            $user['pivot'] = [
                'is_admin' => (bool) $pivot->is_admin,
                'can_request' => (bool) $pivot->can_request,
            ];
        }

        return $user;
    }
}
