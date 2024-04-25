<?php

namespace Artwork\Modules\Project\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "basename" => $this->basename,
            "project_id" => $this->project_id,
            "created_at" => $this->created_at,
            "comments" => CommentResource::collection($this->comments),
            "accessibleUsers" => $this->accessingUsers
        ];
    }
}
