<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class MinimalUserIndexResource extends JsonResource
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
            'id' => $this->getAttribute('id'),
            'first_name' => $this->getAttribute('first_name'),
            'last_name' => $this->getAttribute('last_name'),
            'profile_photo_url' => $this->getAttribute('profile_photo_url'),
            'email' => $this->getAttribute('email'),
            'departments' => $this->getAttribute('departments'),
            'position' => $this->getAttribute('position'),
            'business' => $this->getAttribute('business'),
            'phone_number' => $this->getAttribute('phone_number'),
            'display_name' => $this->getDisplayNameAttribute(),
            'type' => $this->getTypeAttribute(),
        ];
    }
}
