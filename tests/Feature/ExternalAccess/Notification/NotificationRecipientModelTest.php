<?php

namespace Tests\Feature\ExternalAccess\Notification;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class NotificationRecipientModelTest extends TestCase
{
    #[Test]
    public function morph_resolves_to_user(): void
    {
        $user = User::factory()->create();
        $recipient = ExternalAccessNotificationRecipient::create([
            'recipient_type' => $user->getMorphClass(),
            'recipient_id' => $user->id,
            'notification_types' => [NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->value],
        ]);

        $this->assertTrue($recipient->recipient->is($user));
    }

    #[Test]
    public function morph_resolves_to_department(): void
    {
        $department = Department::factory()->create();
        $recipient = ExternalAccessNotificationRecipient::create([
            'recipient_type' => $department->getMorphClass(),
            'recipient_id' => $department->id,
            'notification_types' => [NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED->value],
        ]);

        $this->assertInstanceOf(Department::class, $recipient->recipient);
        $this->assertTrue($recipient->recipient->is($department));
    }

    #[Test]
    public function listens_for_returns_true_for_configured_type(): void
    {
        $recipient = new ExternalAccessNotificationRecipient([
            'notification_types' => [NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->value],
        ]);

        $this->assertTrue($recipient->listensFor(NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED));
        $this->assertFalse($recipient->listensFor(NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED));
    }
}
