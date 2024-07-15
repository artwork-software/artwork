<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Services\EventTypeService;
use Artwork\Modules\SubEvent\Http\Resources\SubEventResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class ProjectCalendarEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClass
    public function toArray($request): array
    {
        /** @var EventTypeService $eventTypeService */
        $eventType = app()->get(EventTypeService::class)->findById($this->event_type_id);

        $shifts = $this->shifts()->with(['shiftsQualifications'])->first();

        $projectName = $this->project()->without([
            'shiftRelevantEventTypes',
            'state'
        ])->first()?->getAttribute('name');

        return [
            'id' => $this->getAttribute('id'),
            'start' => $this->getAttribute('start_time')->utc()->toIso8601String(),
            'startTime' => $this->getAttribute('start_time'),
            'end' => $this->getAttribute('end_time')->utc()->toIso8601String(),
            'allDay' => $this->getAttribute('allDay'),
            'alwaysEventName' => $this->getAttribute('eventName'),
            'eventName' => $this->getAttribute('eventName'),
            'title' => $projectName ?:
                $this->getAttribute('eventName') ?:
                    $eventType->getAttribute('name'),
            'event_type_color' => $eventType->getAttribute('hex_code'),
            'eventTypeColorBackground' => $eventType->getAttribute('hex_code') . '33',
            'eventTypeName' => $eventType->getAttribute('name'),
            'eventTypeAbbreviation' => $eventType->getAttribute('abbreviation'),
            'audience' => $this->getAttribute('audience'),
            'isLoud' => $this->getAttribute('is_loud'),
            'projectName' => $projectName,
            'subEvents' => SubEventResource::collection($this->getAttribute('subEvents'))
        ];
    }
}
