<?php

namespace Tests\Feature\ExternalAccess\AdminSettings;

use Artwork\Modules\Department\Models\Department;
use Artwork\Modules\ExternalAccess\Models\ExternalAccessNotificationRecipient;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RecipientCrudTest extends TestCase
{
    private function crmType(): string
    {
        return NotificationEnum::NOTIFICATION_EXTERNAL_CRM_SUBMITTED->value;
    }

    #[Test]
    public function admin_can_add_user_recipient(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('settings.external-access.recipients.add'), [
            'recipient_type' => 'User',
            'recipient_id' => $user->id,
            'notification_types' => [$this->crmType()],
        ])->assertStatus(302);

        $this->assertDatabaseHas('external_access_notification_recipients', [
            'recipient_type' => User::class,
            'recipient_id' => $user->id,
        ]);
    }

    #[Test]
    public function admin_can_add_department_recipient(): void
    {
        $this->actingAsAdmin();
        $department = Department::factory()->create();

        $this->post(route('settings.external-access.recipients.add'), [
            'recipient_type' => 'Department',
            'recipient_id' => $department->id,
            'notification_types' => [$this->crmType()],
        ])->assertStatus(302);

        $this->assertDatabaseHas('external_access_notification_recipients', [
            'recipient_type' => Department::class,
            'recipient_id' => $department->id,
        ]);
    }

    #[Test]
    public function duplicate_recipient_is_rejected(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();
        ExternalAccessNotificationRecipient::create([
            'recipient_type' => User::class,
            'recipient_id' => $user->id,
            'notification_types' => [$this->crmType()],
        ]);

        $this->post(route('settings.external-access.recipients.add'), [
            'recipient_type' => 'User',
            'recipient_id' => $user->id,
            'notification_types' => [$this->crmType()],
        ])->assertSessionHasErrors('recipient_id');
    }

    #[Test]
    public function recipient_with_invalid_notification_type_is_rejected(): void
    {
        $this->actingAsAdmin();
        $user = User::factory()->create();

        $this->post(route('settings.external-access.recipients.add'), [
            'recipient_type' => 'User',
            'recipient_id' => $user->id,
            'notification_types' => ['NOT_A_REAL_TYPE'],
        ])->assertSessionHasErrors('notification_types.0');
    }

    #[Test]
    public function recipient_can_be_updated(): void
    {
        $this->actingAsAdmin();
        $recipient = ExternalAccessNotificationRecipient::create([
            'recipient_type' => User::class,
            'recipient_id' => User::factory()->create()->id,
            'notification_types' => [$this->crmType()],
        ]);

        $this->patch(route('settings.external-access.recipients.update', $recipient->id), [
            'notification_types' => [
                $this->crmType(),
                NotificationEnum::NOTIFICATION_EXTERNAL_TAB_COMPONENT_UPDATED->value,
            ],
        ])->assertStatus(302);

        $this->assertCount(2, $recipient->fresh()->notification_types);
    }

    #[Test]
    public function recipient_can_be_removed(): void
    {
        $this->actingAsAdmin();
        $recipient = ExternalAccessNotificationRecipient::create([
            'recipient_type' => User::class,
            'recipient_id' => User::factory()->create()->id,
            'notification_types' => [$this->crmType()],
        ]);

        $this->delete(route('settings.external-access.recipients.remove', $recipient->id))->assertStatus(302);

        $this->assertDatabaseMissing('external_access_notification_recipients', ['id' => $recipient->id]);
    }
}
