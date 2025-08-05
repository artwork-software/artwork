<?php

namespace Artwork\Modules\User\Enums;

enum UserFilterTypes: string
{
    case CALENDAR_FILTER = 'calendar_filter';
    case SHIFT_FILTER = 'shift_filter';
    case PLANNING_FILTER = 'planning_filter';

}
