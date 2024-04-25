<?php

namespace Artwork\Modules\Notification\Enums;

enum NotificationGroupEnum: string
{
    case EVENTS = 'EVENTS';

    case BUDGET = 'BUDGET';

    case ROOMS = 'ROOMS';

    case TASKS = 'TASKS';

    case PROJECTS = 'PROJECTS';

    case SHIFTS = 'SHIFTS';

    public function title(): string
    {
        return match ($this) {
            self::EVENTS => "Room assignments & events",
            self::BUDGET => "Project Budgets & Funding Sources",
            self::ROOMS => "Rooms & Room Booking Requests",
            self::TASKS => "Tasks & Checklists",
            self::PROJECTS => "Projects & Teams",
            self::SHIFTS => "Shift Planning",
        };
    }

    // @codingStandardsIgnoreStart
    public function description(): string
    {
        return match ($this) {
            self::EVENTS => "Find out if your room requests have been confirmed or declined, if there are changes to your appointments, and more.",
            self::BUDGET => "Learn the status of your budget calculations, which documents have been released for you, if your funding source has gone into deficit, and more.",
            self::ROOMS => "Find out if there are new booking requests for the rooms you manage. Also, get reminders to process urgent requests, and more.",
            self::TASKS => "Find out if there are new tasks for you and your team. Also, receive reminders to complete urgent tasks, and more.",
            self::PROJECTS => "Find out if there are changes to your projects, if you have been added to a new project or team, and more.",
            self::SHIFTS => "Find out if shifts have been changed, if there were conflicts in shift scheduling, if employees need to be replaced, and more.",
        };
    }
    // @codingStandardsIgnoreEnd
}
