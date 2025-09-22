<?php

namespace Artwork\Modules\Event\Http\Resources;

use Artwork\Modules\Event\Models\Event;
use Artwork\Modules\EventType\Models\EventType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class MinimalInventorySchedulingEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var EventType $eventType */
        $eventType   = $this->getAttribute('event_type');
        $eventName   = $this->getAttribute('eventName');
        $projectName = $this->getAttribute('project')?->getAttribute('name');

        return [
            'id'           => $this->getAttribute('id'),
            'eventName'    => $eventName,
            'title'        => $projectName ?: $eventName ?: $eventType->getAttribute('name'),
            'projectName'  => $projectName,
            'allDay'       => $this->getAttribute('allDay'),
            'timesWithoutDates' => $this->getAttribute('times_without_dates'),
            'eventTypeRelevantForInventory' => $eventType->getAttribute('relevant_for_inventory'),
            'eventTypeColor' => $eventType->getAttribute('hex_code'),
            'period' => CarbonPeriod::create(
                $this->getAttribute('start_time'),
                $this->getAttribute('end_time')
            )->map(fn (Carbon $date) => $date->format('d.m.Y')),
        ];
    }
}
