<?php

namespace Artwork\Modules\Calendar\Traits;

use Artwork\Modules\Event\Models\Event;

trait SerializesEventRelations
{
    private static function serializeSubEvents(Event $event): array
    {
        if (!$event->relationLoaded('subEvents')) {
            return [];
        }

        return $event->subEvents->map(fn ($sub) => [
            'id' => $sub->id,
            'eventName' => $sub->eventName,
            'allDay' => $sub->allDay,
            'formattedDates' => $sub->formattedDates,
            'type' => $sub->type ? [
                'hex_code' => $sub->type->hex_code,
                'abbreviation' => $sub->type->abbreviation,
                'name' => $sub->type->name,
            ] : null,
            'event_properties' => $sub->eventProperties->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'icon' => $p->icon ?? null,
            ])->values()->all(),
        ])->values()->all();
    }

    private static function serializeEventProperties(Event $event): array
    {
        if (!$event->relationLoaded('eventProperties')) {
            return [];
        }

        return $event->eventProperties->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'icon' => $p->icon ?? null,
        ])->values()->all();
    }
}
