<?php

namespace Tests\Feature\Http\Controllers;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class NotificationControllerTest extends FeatureTestCase
{
    #[Test]
    public function guest_cannot_view_notifications(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_view_notifications(): void
    {
        $this->actingAsAdmin();

        $this->get(route('notifications.index'))->assertOk();
    }

    #[Test]
    public function guest_cannot_set_read_at(): void
    {
        $this->patch(route('notifications.setReadAt'), ['notificationId' => 'X'])
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_set_read_at_no_op(): void
    {
        $this->actingAsAdmin();

        // Pass an unknown id; service returns null, controller no-ops
        $response = $this->patch(route('notifications.setReadAt'), [
            'notificationId' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertOk();
    }

    #[Test]
    public function admin_can_set_on_read_all_with_empty(): void
    {
        $this->actingAsAdmin();

        $response = $this->patch(route('notifications.setReadAtAll'), [
            'notificationIds' => [],
        ]);

        $response->assertOk();
    }

    #[Test]
    public function admin_can_delete_unknown_notification(): void
    {
        $this->actingAsAdmin();

        $response = $this->delete(route('notifications.delete', 'not-existing'));

        $response->assertOk();
    }
}
