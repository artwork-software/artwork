<?php

namespace Artwork\Modules\User\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserIconResource extends JsonResource
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
            'profile_photo_url' => $this->profile_photo_url
        ];
    }
}
