<?php

namespace Artwork\Modules\MoneySource\Http\Resources;

use Artwork\Modules\Project\Http\Resources\CommentResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MoneySourceFileResource extends JsonResource
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
            "money_source_id" => $this->money_source_id,
            "created_at" => $this->created_at,
            "comments" => CommentResource::collection($this->comments),
        ];
    }
}
