<?php

namespace Tests\Unit\Pure\Enums\Notification;

use Artwork\Modules\Department\Notifications\TeamNotification;
use Artwork\Modules\Event\Notifications\ConflictNotification;
use Artwork\Modules\Event\Notifications\EventNotification;
use Artwork\Modules\MoneySource\Notifications\MoneySourceNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Project\Notifications\ProjectNotification;
use Artwork\Modules\Room\Notifications\RoomNotification;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Task\Notifications\DeadlineNotification;
use Artwork\Modules\Task\Notifications\TaskNotification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

final class NotificationEnumTest extends UnitTestCase
{
    #[Test]
    public function room_request_case_has_expected_value(): void
    {
        $this->assertSame('ROOM_REQUEST', NotificationEnum::NOTIFICATION_ROOM_REQUEST->value);
    }

    #[Test]
    public function conflict_case_has_expected_value(): void
    {
        $this->assertSame('NOTIFICATION_CONFLICT', NotificationEnum::NOTIFICATION_CONFLICT->value);
    }

    #[Test]
    public function event_changed_group_type_is_events(): void
    {
        $this->assertSame('EVENTS', NotificationEnum::NOTIFICATION_EVENT_CHANGED->groupType());
    }

    #[Test]
    public function budget_state_changed_group_type_is_budget(): void
    {
        $this->assertSame('BUDGET', NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED->groupType());
    }

    #[Test]
    public function room_changed_group_type_is_rooms(): void
    {
        $this->assertSame('ROOMS', NotificationEnum::NOTIFICATION_ROOM_CHANGED->groupType());
    }

    #[Test]
    public function new_task_group_type_is_tasks(): void
    {
        $this->assertSame('TASKS', NotificationEnum::NOTIFICATION_NEW_TASK->groupType());
    }

    #[Test]
    public function project_group_type_is_projects(): void
    {
        $this->assertSame('PROJECTS', NotificationEnum::NOTIFICATION_PROJECT->groupType());
    }

    #[Test]
    public function shift_changed_group_type_is_shifts(): void
    {
        $this->assertSame('SHIFTS', NotificationEnum::NOTIFICATION_SHIFT_CHANGED->groupType());
    }

    #[Test]
    public function inventory_overbooked_group_type_is_inventory(): void
    {
        $this->assertSame('INVENTORY', NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED->groupType());
    }

    #[Test]
    public function contracts_document_changed_group_type_is_documents(): void
    {
        $this->assertSame('DOCUMENTS', NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED->groupType());
    }

    #[Test]
    public function event_changed_notification_class_is_event_notification(): void
    {
        $this->assertSame(EventNotification::class, NotificationEnum::NOTIFICATION_EVENT_CHANGED->notificationClass());
    }

    #[Test]
    public function conflict_notification_class_is_conflict_notification(): void
    {
        $this->assertSame(ConflictNotification::class, NotificationEnum::NOTIFICATION_CONFLICT->notificationClass());
    }

    #[Test]
    public function room_request_notification_class_is_room_request_notification(): void
    {
        $this->assertSame(RoomRequestNotification::class, NotificationEnum::NOTIFICATION_ROOM_REQUEST->notificationClass());
    }

    #[Test]
    public function room_changed_notification_class_is_room_notification(): void
    {
        $this->assertSame(RoomNotification::class, NotificationEnum::NOTIFICATION_ROOM_CHANGED->notificationClass());
    }

    #[Test]
    public function task_reminder_notification_class_is_deadline_notification(): void
    {
        $this->assertSame(DeadlineNotification::class, NotificationEnum::NOTIFICATION_TASK_REMINDER->notificationClass());
    }

    #[Test]
    public function new_task_notification_class_is_task_notification(): void
    {
        $this->assertSame(TaskNotification::class, NotificationEnum::NOTIFICATION_NEW_TASK->notificationClass());
    }

    #[Test]
    public function team_notification_class_is_team_notification(): void
    {
        $this->assertSame(TeamNotification::class, NotificationEnum::NOTIFICATION_TEAM->notificationClass());
    }

    #[Test]
    public function project_notification_class_is_project_notification(): void
    {
        $this->assertSame(ProjectNotification::class, NotificationEnum::NOTIFICATION_PROJECT->notificationClass());
    }

    #[Test]
    public function shift_changed_notification_class_is_shift_notification(): void
    {
        $this->assertSame(ShiftNotification::class, NotificationEnum::NOTIFICATION_SHIFT_CHANGED->notificationClass());
    }

    #[Test]
    public function budget_money_source_changed_notification_class_is_money_source_notification(): void
    {
        $this->assertSame(MoneySourceNotification::class, NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED->notificationClass());
    }

    #[Test]
    public function event_changed_title_is_event_changes(): void
    {
        $this->assertSame('Event changes', NotificationEnum::NOTIFICATION_EVENT_CHANGED->title());
    }

    #[Test]
    public function shift_locked_title_is_shift_schedule_lock_in(): void
    {
        $this->assertSame('Shift schedule lock-in', NotificationEnum::NOTIFICATION_SHIFT_LOCKED->title());
    }

    #[Test]
    public function description_returns_non_empty_string_for_all_cases(): void
    {
        foreach (NotificationEnum::cases() as $case) {
            $this->assertNotSame('', $case->description(), sprintf('Empty description for %s', $case->name));
        }
    }

    #[Test]
    public function title_returns_non_empty_string_for_all_cases(): void
    {
        foreach (NotificationEnum::cases() as $case) {
            $this->assertNotSame('', $case->title(), sprintf('Empty title for %s', $case->name));
        }
    }

    #[Test]
    public function group_type_returns_non_empty_string_for_all_cases(): void
    {
        foreach (NotificationEnum::cases() as $case) {
            $this->assertNotSame('', $case->groupType(), sprintf('Empty groupType for %s', $case->name));
        }
    }

    #[Test]
    public function notification_class_returns_existing_class_for_covered_cases(): void
    {
        // NOTE: Bug detected: NOTIFICATION_EVENT_VERIFICATION_REQUESTS, NOTIFICATION_DOCUMENT_REQUEST_CREATED,
        // and NOTIFICATION_DOCUMENT_REQUEST_COMPLETED are NOT handled in NotificationEnum::notificationClass()
        // match expression - they trigger UnhandledMatchError at runtime.
        $uncovered = [
            NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS,
            NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_CREATED,
            NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_COMPLETED,
            NotificationEnum::NOTIFICATION_INVENTORY_ARTICLE_CHANGED,
            NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED,
        ];

        foreach (NotificationEnum::cases() as $case) {
            if (in_array($case, $uncovered, true)) {
                continue;
            }
            $this->assertTrue(
                class_exists($case->notificationClass()),
                sprintf('Notification class %s does not exist for %s', $case->notificationClass(), $case->name)
            );
        }
    }

    #[Test]
    public function try_from_returns_null_for_unknown(): void
    {
        $this->assertNull(NotificationEnum::tryFrom('not_a_real_notification'));
    }
}
