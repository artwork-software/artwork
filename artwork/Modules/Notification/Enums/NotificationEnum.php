<?php

namespace Artwork\Modules\Notification\Enums;

use Artwork\Modules\Department\Notifications\TeamNotification;
use Artwork\Modules\Event\Notifications\ConflictNotification;
use Artwork\Modules\Event\Notifications\EventNotification;
use Artwork\Modules\MoneySource\Notifications\MoneySourceNotification;
use Artwork\Modules\Project\Notifications\ProjectNotification;
use Artwork\Modules\Room\Notifications\RoomNotification;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Task\Notifications\DeadlineNotification;
use Artwork\Modules\Task\Notifications\TaskNotification;

enum NotificationEnum: string
{
    case NOTIFICATION_ROOM_REQUEST = 'ROOM_REQUEST';

    case NOTIFICATION_CONFLICT = 'NOTIFICATION_CONFLICT';

    case NOTIFICATION_EVENT_CHANGED = 'NOTIFICATION_EVENT_CHANGED';

    case NOTIFICATION_LOUD_ADJOINING_EVENT = 'NOTIFICATION_LOUD_ADJOINING_EVENT';

    case NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED = 'NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED';

    case NOTIFICATION_BUDGET_STATE_CHANGED = 'NOTIFICATION_BUDGET_STATE_CHANGED';

    case NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED = 'NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED';

    case NOTIFICATION_MONEY_SOURCE_EXPIRATION = 'NOTIFICATION_MONEY_SOURCE_EXPIRATION';

    case NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED = 'NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED';

    case NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED = 'NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED';

    case NOTIFICATION_UPSERT_ROOM_REQUEST = 'NOTIFICATION_UPSERT_ROOM_REQUEST';

    case NOTIFICATION_REMINDER_ROOM_REQUEST = 'NOTIFICATION_REMINDER_ROOM_REQUEST';

    case NOTIFICATION_ROOM_CHANGED = 'NOTIFICATION_ROOM_CHANGED';

    case NOTIFICATION_ROOM_ANSWER = 'NOTIFICATION_ROOM_ANSWER';

    case NOTIFICATION_NEW_TASK = 'NOTIFICATION_NEW_TASK';

    case NOTIFICATION_TASK_REMINDER = 'TASK_REMINDER';

    case NOTIFICATION_TASK_CHANGED = 'TASK_CHANGED';

    case NOTIFICATION_PROJECT = 'NOTIFICATION_PROJECT';

    case NOTIFICATION_PUBLIC_RELEVANT = 'NOTIFICATION_PUBLIC_RELEVANT';

    case NOTIFICATION_TEAM = 'NOTIFICATION_TEAM';

    case NOTIFICATION_SHIFT_CHANGED = 'NOTIFICATION_SHIFT_CHANGED';

    //demand
    case NOTIFICATION_SHIFT_OPEN_DEMAND = 'NOTIFICATION_SHIFT_OPEN_DEMAND';

    case NOTIFICATION_SHIFT_OWN_INFRINGEMENT = 'NOTIFICATION_SHIFT_OWN_INFRINGEMENT';

    case NOTIFICATION_SHIFT_INFRINGEMENT = 'NOTIFICATION_SHIFT_INFRINGEMENT';

    case NOTIFICATION_SHIFT_LOCKED = 'NOTIFICATION_SHIFT_LOCKED';

    case NOTIFICATION_SHIFT_AVAILABLE = 'NOTIFICATION_SHIFT_AVAILABLE';

    case NOTIFICATION_SHIFT_CONFLICT = 'NOTICATION_SHIFT_CONFLICT';

    public function groupType(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_REQUEST,
            self::NOTIFICATION_CONFLICT,
            self::NOTIFICATION_EVENT_CHANGED,
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "EVENTS",

            self::NOTIFICATION_BUDGET_STATE_CHANGED,
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED,
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED,
            self::NOTIFICATION_MONEY_SOURCE_EXPIRATION,
            self::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED,
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED => "BUDGET",

            self::NOTIFICATION_UPSERT_ROOM_REQUEST,
            self::NOTIFICATION_REMINDER_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_CHANGED => "ROOMS",

            self::NOTIFICATION_NEW_TASK,
            self::NOTIFICATION_TASK_REMINDER,
            self::NOTIFICATION_TASK_CHANGED => "TASKS",

            self::NOTIFICATION_PROJECT,
            self::NOTIFICATION_PUBLIC_RELEVANT,
            self::NOTIFICATION_TEAM => "PROJECTS",

            self::NOTIFICATION_SHIFT_CHANGED,
            self::NOTIFICATION_SHIFT_OWN_INFRINGEMENT,
            self::NOTIFICATION_SHIFT_INFRINGEMENT,
            self::NOTIFICATION_SHIFT_LOCKED,
            self::NOTIFICATION_SHIFT_AVAILABLE,
            self::NOTIFICATION_SHIFT_OPEN_DEMAND,
            self::NOTIFICATION_SHIFT_CONFLICT => "SHIFTS",
        };
    }

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh
    public function notificationClass(): string
    {
        return match ($this) {
            self::NOTIFICATION_EVENT_CHANGED => EventNotification::class,
            self::NOTIFICATION_UPSERT_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_REQUEST => RoomRequestNotification::class,
            self::NOTIFICATION_CONFLICT,
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => ConflictNotification::class,
            self::NOTIFICATION_REMINDER_ROOM_REQUEST,
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_CHANGED => RoomNotification::class,
            self::NOTIFICATION_TASK_REMINDER => DeadlineNotification::class,
            self::NOTIFICATION_NEW_TASK,
            self::NOTIFICATION_TASK_CHANGED => TaskNotification::class,
            self::NOTIFICATION_PUBLIC_RELEVANT,
            self::NOTIFICATION_PROJECT => ProjectNotification::class,
            self::NOTIFICATION_TEAM => TeamNotification::class,
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED,
            self::NOTIFICATION_BUDGET_STATE_CHANGED,
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED,
            self::NOTIFICATION_MONEY_SOURCE_EXPIRATION,
            self::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED,
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED => MoneySourceNotification::class,
            self::NOTIFICATION_SHIFT_CHANGED,
            self::NOTIFICATION_SHIFT_OWN_INFRINGEMENT,
            self::NOTIFICATION_SHIFT_INFRINGEMENT,
            self::NOTIFICATION_SHIFT_LOCKED,
            self::NOTIFICATION_SHIFT_AVAILABLE,
            self::NOTIFICATION_SHIFT_OPEN_DEMAND,
            self::NOTIFICATION_SHIFT_CONFLICT => ShiftNotification::class,
        };
    }

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    public function title(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_REQUEST => "Room requests confirmed or declined",
            self::NOTIFICATION_CONFLICT => "Event conflicts",
            self::NOTIFICATION_EVENT_CHANGED => "Event changes",
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "Side events",

            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED => 'Budget and funding source access',
            self::NOTIFICATION_BUDGET_STATE_CHANGED => 'Changes to budget status',
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED => 'Changes to budget and funding sources',
            self::NOTIFICATION_MONEY_SOURCE_EXPIRATION => 'Funding source is expiring',
            self::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED =>
                'Funding source has reached the set threshold',
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED => 'Changes to documents and contracts',

            self::NOTIFICATION_UPSERT_ROOM_REQUEST => "New/modified room request",
            self::NOTIFICATION_REMINDER_ROOM_REQUEST => "Reminder for room requests",
            self::NOTIFICATION_ROOM_CHANGED => "Changes to room",

            self::NOTIFICATION_NEW_TASK => "New tasks",
            self::NOTIFICATION_TASK_REMINDER => "Reminders for Tasks",
            self::NOTIFICATION_TASK_CHANGED => "Changes to tasks",

            self::NOTIFICATION_PROJECT => "Changes in projects & project groups",
            self::NOTIFICATION_PUBLIC_RELEVANT => 'Public relations-related changes',
            self::NOTIFICATION_TEAM => "Team membership",

            self::NOTIFICATION_SHIFT_CHANGED => "Changes to my shifts",
            self::NOTIFICATION_SHIFT_OWN_INFRINGEMENT => "Warning regarding legal regulations (your shifts)",
            self::NOTIFICATION_SHIFT_INFRINGEMENT => "Warning regarding legal regulations (shift planning)",
            self::NOTIFICATION_SHIFT_LOCKED => "Shift schedule lock-in",
            self::NOTIFICATION_SHIFT_AVAILABLE => "Availabilities",
            self::NOTIFICATION_SHIFT_OPEN_DEMAND => "Open demands",
            self::NOTIFICATION_SHIFT_CONFLICT => "Availabilities & Conflicts",
        };
    }

    //phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded
    // @codingStandardsIgnoreStart
    public function description(): string
    {
        return match ($this) {
            self::NOTIFICATION_ROOM_ANSWER,
            self::NOTIFICATION_ROOM_REQUEST => "Find out if your room requests have been confirmed or declined.",
            self::NOTIFICATION_CONFLICT => "Be notified as soon as someone schedules an appointment that conflicts with one of your appointments.",
            self::NOTIFICATION_EVENT_CHANGED => "Find out if there have been any changes to your appointments or if an appointment has been cancelled.",
            self::NOTIFICATION_LOUD_ADJOINING_EVENT => "Find out whether loud events or events with an audience have been set in an adjacent room at the same time as one of your events.",
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED => 'Get notified when your access to project budgets or funding sources has changed.',
            self::NOTIFICATION_BUDGET_STATE_CHANGED => 'Get notified when parts of your calculation have been committed, requested for verification or verified.',
            self::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED => 'Get notified when there are changes to your projects budget, funding source or copyrights. You will also receive a warning as soon as your funding source has slipped into the red.',
            self::NOTIFICATION_MONEY_SOURCE_EXPIRATION =>
                'You will be notified as soon as the funding source expires.',
            self::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED =>
                'You will be notified as soon as the funding source reaches the defined threshold.',
            self::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED => 'Find out whether you have received approval for documents or contracts and whether there have been any changes to these documents.',
            self::NOTIFICATION_UPSERT_ROOM_REQUEST => "Find out if there are new or changed room requests.",
            self::NOTIFICATION_REMINDER_ROOM_REQUEST => "Be reminded when room requests become urgent.",
            self::NOTIFICATION_ROOM_CHANGED => "You will be notified as soon as there are changes to your rooms or your room responsibilities.",
            self::NOTIFICATION_NEW_TASK => "Find out if there are new tasks for you or your team.",
            self::NOTIFICATION_TASK_REMINDER => "Be reminded when tasks become urgent or have already have already exceeded their deadline.",
            self::NOTIFICATION_TASK_CHANGED => "Find out if there are any changes to your tasks",
            self::NOTIFICATION_PROJECT => "Find out if there are any changes in your projects or groups and what role you have in the project team.",
            self::NOTIFICATION_PUBLIC_RELEVANT => 'Be notified as soon as there are changes to your projects that may affect public relations.',
            self::NOTIFICATION_TEAM => "You will be notified as soon as your team membership changes.",
            self::NOTIFICATION_SHIFT_CHANGED => "Find out whether your shifts have been changed, you have been reassigned or deleted from a shift.",
            self::NOTIFICATION_SHIFT_OWN_INFRINGEMENT => "Find out whether your shift planning collides with legal regulations, e.g. if you have been scheduled for too long at a time or too few breaks.",
            self::NOTIFICATION_SHIFT_INFRINGEMENT => "Find out whether your shift planning for others collides with legal regulations, e.g. if you have scheduled one user for too long at a time or with too few breaks.",
            self::NOTIFICATION_SHIFT_LOCKED => "Find out whether shift schedules conflict with legal regulations,e.g. employees have been scheduled with too few breaks.",
            self::NOTIFICATION_SHIFT_AVAILABLE => "Find out if someone has made changes to your availability.",
            self::NOTIFICATION_SHIFT_CONFLICT => "Find out if an employee has a new availability or you need to fill someone new.",
            self::NOTIFICATION_SHIFT_OPEN_DEMAND => "Find out if there are any open demands for your shifts.",
        };
    }
    // @codingStandardsIgnoreEnd
}
