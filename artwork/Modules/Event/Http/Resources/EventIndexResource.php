<?php

namespace Artwork\Modules\Event\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventIndexResource extends JsonResource
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
            'event_name' => $this->name,
            'event_type' => $this->event_type,
            'project' => $this->project,
        ];
    }
}
