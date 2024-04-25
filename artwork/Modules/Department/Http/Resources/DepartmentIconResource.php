<?php

namespace Artwork\Modules\Department\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Artwork\Modules\Department\Models\Department
 */
class DepartmentIconResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, string>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'resource' => class_basename($this),
            'id' => $this->id,
            'name' => $this->name,
            'svg_name' => $this->svg_name ?? 'icon_ausstellung',
        ];
    }
}
