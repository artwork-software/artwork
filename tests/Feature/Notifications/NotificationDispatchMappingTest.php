<?php

namespace Tests\Feature\Notifications;

use Artwork\Modules\Budget\Notifications\BudgetVerified;
use Artwork\Modules\Department\Notifications\TeamNotification;
use Artwork\Modules\Event\Notifications\ConflictNotification;
use Artwork\Modules\Event\Notifications\EventNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalAccessExpiringNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalCrmSubmissionNotification;
use Artwork\Modules\ExternalAccess\Notifications\ExternalTabComponentUpdatedNotification;
use Artwork\Modules\Inventory\Notifications\InventoryArticleNotification;
use Artwork\Modules\MoneySource\Notifications\MoneySourceNotification;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationService;
use Artwork\Modules\Project\Notifications\ProjectNotification;
use Artwork\Modules\Room\Notifications\RoomNotification;
use Artwork\Modules\Room\Notifications\RoomRequestNotification;
use Artwork\Modules\Shift\Notifications\ShiftNotification;
use Artwork\Modules\Task\Notifications\DeadlineNotification;
use Artwork\Modules\Task\Notifications\TaskNotification;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Welche Laravel-Notification NotificationService::createNotification je Typ verschickt und ob
 * auch die auslösende Person selbst benachrichtigt wird. Deckt jeden Enum-Fall ab – ein neuer Typ
 * ohne Eintrag hier fällt sofort auf.
 */
final class NotificationDispatchMappingTest extends FeatureTestCase
{
    /**
     * @return array<string, array{NotificationEnum, class-string|null, bool}>
     */
    public static function mapping(): array
    {
        $map = [
            [NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST, RoomRequestNotification::class, false],
            [NotificationEnum::NOTIFICATION_ROOM_REQUEST, RoomRequestNotification::class, false],
            [NotificationEnum::NOTIFICATION_ROOM_ANSWER, RoomRequestNotification::class, false],
            [NotificationEnum::NOTIFICATION_EVENT_CHANGED, EventNotification::class, false],
            [NotificationEnum::NOTIFICATION_NEW_TASK, TaskNotification::class, false],
            [NotificationEnum::NOTIFICATION_TASK_CHANGED, TaskNotification::class, false],
            [NotificationEnum::NOTIFICATION_PROJECT, ProjectNotification::class, false],
            [NotificationEnum::NOTIFICATION_PUBLIC_RELEVANT, ProjectNotification::class, false],
            [NotificationEnum::NOTIFICATION_TEAM, TeamNotification::class, false],
            [NotificationEnum::NOTIFICATION_ROOM_CHANGED, RoomNotification::class, false],
            [NotificationEnum::NOTIFICATION_CONFLICT, ConflictNotification::class, false],
            [NotificationEnum::NOTIFICATION_LOUD_ADJOINING_EVENT, ConflictNotification::class, false],
            [NotificationEnum::NOTIFICATION_TASK_REMINDER, DeadlineNotification::class, false],
            [NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_AUTH_CHANGED, MoneySourceNotification::class, false],
            [NotificationEnum::NOTIFICATION_BUDGET_MONEY_SOURCE_CHANGED, MoneySourceNotification::class, false],
            [NotificationEnum::NOTIFICATION_MONEY_SOURCE_EXPIRATION, MoneySourceNotification::class, true],
            [NotificationEnum::NOTIFICATION_MONEY_SOURCE_BUDGET_THRESHOLD_REACHED, MoneySourceNotification::class, true],
            [NotificationEnum::NOTIFICATION_BUDGET_STATE_CHANGED, BudgetVerified::class, false],
            [NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED, BudgetVerified::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_LOCKED, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_AVAILABLE, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_CHANGED, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_CONFLICT, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_INFRINGEMENT, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_OWN_INFRINGEMENT, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_REQUEST_APPROVED, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_REQUEST_DECLINED, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_WORKTIME_GET_REQUEST, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_SHIFT_WORKER_CONFIRMATION, ShiftNotification::class, false],
            [NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS, EventNotification::class, false],
            [NotificationEnum::NOTIFICATION_INVENTORY_OVERBOOKED, InventoryArticleNotification::class, false],
            [NotificationEnum::NOTIFICATION_INVENTORY_ARTICLE_CHANGED, InventoryArticleNotification::class, false],
            [NotificationEnum::NOTIFICATION_EXTERNAL_ISSUE_RETURN_DUE, InventoryArticleNotification::class, true],
            [NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_CREATED, BudgetVerified::class, false],
            [NotificationEnum::NOTIFICATION_DOCUMENT_REQUEST_COMPLETED, BudgetVerified::class, false],
            [NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED, ExternalCrmSubmissionNotification::class, false],
            [
                NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED,
                ExternalTabComponentUpdatedNotification::class,
                false,
            ],
            [NotificationEnum::NOTIFICATION_EXTERNAL_ACCESS_EXPIRING, ExternalAccessExpiringNotification::class, false],
            // Nur Push/Indikator, keine Laravel-Notification
            [NotificationEnum::NOTIFICATION_REMINDER_ROOM_REQUEST, null, false],
        ];

        $cases = [];
        foreach ($map as $row) {
            $cases[$row[0]->name] = $row;
        }

        return $cases;
    }

    #[Test]
    public function every_notification_type_has_an_expectation(): void
    {
        $this->assertEqualsCanonicalizing(
            array_column(NotificationEnum::cases(), 'name'),
            array_keys(self::mapping())
        );
    }

    #[Test]
    #[DataProvider('mapping')]
    public function the_type_decides_which_notification_reaches_the_recipient(
        NotificationEnum $type,
        ?string $expectedClass,
        bool $alsoToActingUser
    ): void {
        $actingUser = $this->actingAsAdmin();
        $recipient = User::factory()->create();

        $this->notify($type, $recipient);

        if ($expectedClass === null) {
            Notification::assertNothingSent();

            return;
        }
        Notification::assertSentTo($recipient, $expectedClass);
        $this->assertSame($expectedClass, $type->notificationClass());
        $this->assertSame($alsoToActingUser, $type->notifiesActingUser());

        $this->notify($type, $actingUser);
        $alsoToActingUser
            ? Notification::assertSentTo($actingUser, $expectedClass)
            : Notification::assertNotSentTo($actingUser, $expectedClass);
    }

    #[Test]
    public function a_missing_broadcast_message_does_not_break_sending(): void
    {
        $this->actingAsAdmin();
        $recipient = User::factory()->create();

        // vorher: TypeError, weil null an BaseNotification::$broadcastMessage (array) ging
        $this->notify(NotificationEnum::NOTIFICATION_TEAM, $recipient, null);

        Notification::assertSentTo($recipient, TeamNotification::class);
    }

    /**
     * @param array<string, string>|null $broadcastMessage
     */
    private function notify(
        NotificationEnum $type,
        User $recipient,
        ?array $broadcastMessage = ['type' => 'success', 'message' => 'Test']
    ): void {
        $service = app(NotificationService::class);
        $service->setNotificationConstEnum($type);
        $service->setNotificationTo($recipient);
        $service->setTitle('Test');
        $service->setBroadcastMessage($broadcastMessage);
        $service->createNotification();
    }
}
