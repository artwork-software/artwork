<?php

namespace Artwork\Modules\User\Http\Resources;

use Artwork\Modules\Craft\Models\Craft;
use Artwork\Modules\User\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserWorkProfileResource extends JsonResource
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
            'work_name' => $this->work_name,
            'work_description' => $this->work_description,
            'can_work_shifts' => $this->can_work_shifts,
            'accessibleCrafts' => Craft::query()
                ->where('assignable_by_all', '=', true)
                ->get()
                ->merge($this->crafts)
                ->toArray(),
            'assignedCrafts' => $this->assignedCrafts,
            'assignableCrafts' => Craft::query()->get()->filter(
                fn($craft) => !$this->assignedCrafts->pluck('id')->contains($craft->id)
            )->toArray(),
            'shiftQualifications' => $this->shiftQualifications
        ];
    }
}
