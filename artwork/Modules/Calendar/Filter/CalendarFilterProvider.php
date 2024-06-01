<?php

namespace Artwork\Modules\Calendar\Filter;

interface CalendarFilterProvider
{
    public function getCalendarFilter(): ?CalendarFilter;
}
