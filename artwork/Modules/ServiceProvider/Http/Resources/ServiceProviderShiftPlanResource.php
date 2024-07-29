<?php

namespace Artwork\Modules\ServiceProvider\Http\Resources;

use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Abstracts\WorkerShiftPlanResource;

/**
 * @mixin ServiceProvider
 */
class ServiceProviderShiftPlanResource extends WorkerShiftPlanResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return array_merge(
            [
                'provider_name' => $this->getAttribute('provider_name'),
                'profile_photo_url' => $this->getAttribute('profile_image'),
            ],
            parent::toArray($request)
        );
    }
}
