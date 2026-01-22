<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserWorkProfileResource extends JsonResource
{
    public static $wrap = null;

    private Collection $crafts;

    public function __construct(
        $resource,
        Collection $crafts
    ) {
        parent::__construct($resource);

        $this->crafts = $crafts;
    }

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        $assignedCrafts = $this->getAttribute('assignedCrafts');

        return [
            'resource' => class_basename($this),
            'id' => $this->getAttribute('id'),
            'first_name' => $this->getAttribute('first_name'),
            'last_name' => $this->getAttribute('last_name'),
            'profile_photo_url' => $this->getAttribute('profile_photo_url'),
            'work_name' => $this->getAttribute('work_name'),
            'work_description' => $this->getAttribute('work_description'),
            'can_work_shifts' => $this->getAttribute('can_work_shifts'),
            'accessibleCrafts' => $this->can('can plan shifts') ?
                $this->crafts->filter(fn(Craft $craft) =>
                    $craft->getAttribute('assignable_by_all') === true ||
                    $craft->craftShiftPlaner->contains($this->resource->id)
                )
                    ->merge($this->getAttribute('assignedCrafts'))
                    ->merge($this->getAttribute('managingCrafts'))
                    ->unique('id')
                    ->values()
                    ->toArray() :
                [],
            'assignedCrafts' => $assignedCrafts,
            'assignableCrafts' => $this->crafts->filter(
                fn($craft) => !$assignedCrafts->pluck('id')->contains($craft->getAttribute('id'))
            )->toArray(),
            'shiftQualifications' => $this->getAttribute('shiftQualifications'),
            'is_freelancer' => $this->getAttribute('is_freelancer'),
            'email' => $this->getAttribute('email'),
        ];
    }
}
