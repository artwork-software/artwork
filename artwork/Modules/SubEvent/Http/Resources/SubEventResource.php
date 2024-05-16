<?php

namespace Artwork\Modules\SubEvent\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubEventResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->eventName,
            'description' => $this->description,
            'start' => $this->start_time,
            'end' => $this->end_time,
            'audience' => $this->audience,
            'is_loud' => $this->is_loud,
            'eventTypeName' => $this->type->name,
            'eventTypeId' => $this->type->id,
            'eventType' => $this->type,
            'eventTypeAbbreviation' => $this->type->abbreviation,
            'eventTypeColor' => $this->type->hex_code,
            'allDay' => $this->allDay,
        ];
    }
}
