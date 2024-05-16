<?php

namespace Artwork\Modules\EventType\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventTypeResource extends JsonResource
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
            'hex_code' => $this->hex_code,
            'project_mandatory' => $this->project_mandatory,
            'individual_name' => $this->individual_name,
            'abbreviation' => $this->abbreviation,
        ];
    }
}
