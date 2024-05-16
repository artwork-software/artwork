<?php

namespace Artwork\Modules\Project\Http\Resources;

use Artwork\Modules\User\Http\Resources\UserIconResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            "id" => $this->id,
            "text" => $this->text,
            "project_id" => $this->project_id,
            "project_file_id" => $this->project_file_id,
            "money_source_file_id" => $this->money_source_file_id,
            "contract_id" => $this->contract_id,
            "user" => UserIconResource::make($this->user),
            "created_at" => $this->created_at->format('d.m.Y, H:i'),
        ];
    }
}
