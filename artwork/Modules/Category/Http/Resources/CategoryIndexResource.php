<?php

namespace Artwork\Modules\Category\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Category\Models\Category
 */
class CategoryIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
