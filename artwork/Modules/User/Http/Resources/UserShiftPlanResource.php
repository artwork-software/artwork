<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\Shift\Abstracts\WorkerShiftPlanResource;
use Artwork\Modules\User\Models\User;

/**
 * @mixin User
 */
class UserShiftPlanResource extends WorkerShiftPlanResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return array_merge(
            [
                'first_name' => $this->getAttribute('first_name'),
                'last_name' => $this->getAttribute('last_name'),
                'profile_photo_url' => $this->getAttribute('profile_photo_url'),
                'project_management' => $this->can(PermissionEnum::PROJECT_MANAGEMENT->value),
                'display_name' => $this->getDisplayNameAttribute(),
                'is_freelancer' => $this->getAttribute('is_freelancer'),
                'position' => $this->getAttribute('position'),
                'pronouns' => $this->getAttribute('pronouns'),
                'description' => $this->getAttribute('description'),
                'email' => $this->getAttribute('email'),
                'phone_number' => $this->getAttribute('phone_number'),
                'email_private' => (bool) $this->getAttribute('email_private'),
                'phone_private' => (bool) $this->getAttribute('phone_private'),
            ],
            parent::toArray($request)
        );
    }
}
