<?php

namespace Artwork\Modules\User\Enums;

enum UserFilterTypes: string
{
    case CALENDAR_FILTER = 'calendar_filter';
    case SHIFT_FILTER = 'shift_filter';
    case PROJECT_SHIFT_FILTER = 'project_shift_filter';
    case PLANNING_FILTER = 'planning_filter';
    case CALENDAR_DAILY_FILTER = 'calendar_daily_filter';
    case SHIFT_DAILY_FILTER = 'shift_daily_filter';
    case PLANNING_DAILY_FILTER = 'planning_daily_filter';

}
