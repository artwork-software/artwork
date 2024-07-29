<?php

namespace Artwork\Modules\Freelancer\Http\Resources;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\Shift\Abstracts\WorkerShiftPlanResource;

/**
 * @mixin Freelancer
 */
class FreelancerShiftPlanResource extends WorkerShiftPlanResource
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
                'profile_photo_url' => $this->getAttribute('profile_image'),
            ],
            parent::toArray($request)
        );
    }
}
