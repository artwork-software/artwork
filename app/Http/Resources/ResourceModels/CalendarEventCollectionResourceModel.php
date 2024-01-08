<?php

namespace App\Http\Resources\ResourceModels;

use Illuminate\Support\Collection;

class CalendarEventCollectionResourceModel
{
    public function __construct(
        public readonly Collection $areas,
        public readonly Collection $projects,
        public readonly Collection $eventTypes,
        public readonly Collection $roomCategories,
        public readonly Collection $roomAttributes,
        public readonly Collection $events,
        public readonly Collection $filter,
    ) {
    }
}
