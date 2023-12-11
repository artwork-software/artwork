<?php

namespace App\Http\Resources\ResourceModels;

use Illuminate\Support\Collection;

class CalendarEventCollectionResourceModel
{
    /**
     * @param Collection $areas
     * @param Collection $projects
     * @param Collection $eventTypes
     * @param Collection $roomCategories
     * @param Collection $roomAttributes
     * @param Collection $events
     * @param Collection $filter
     */
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
