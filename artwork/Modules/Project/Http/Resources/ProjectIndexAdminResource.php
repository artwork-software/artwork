<?php

namespace Artwork\Modules\Project\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Project\Models\Project
 */
class ProjectIndexAdminResource extends JsonResource
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
            'access_budget' => $this->access_budget,
            'project_managers' => $this->managerUsers,
            'can_write' => $this->writeUsers,
        ];
    }
}
