<?php

namespace Tests\Feature\Http\Controllers;

use Artwork\Modules\Event\Models\Event;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{string}>
     */
    public static function eventDialogs(): array
    {
        return [
            'Belegung absagen' => ['openDeclineEvent'],
            'Bearbeiten/Annehmen' => ['openEditEvent'],
        ];
    }

    #[Test]
    #[DataProvider('eventDialogs')]
    public function event_dialogs_get_the_event_unwrapped(string $dialogFlag): void
    {
        $this->actingAsAdmin();
        $event = Event::factory()->create();

        // vorher {data: {...}}: der Absage-Dialog las event.id → route('events.decline') ohne Termin
        $this->get(route('notifications.index', [$dialogFlag => true, 'eventId' => $event->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('event.id', $event->id)
                ->where('event.roomId', $event->room_id)
                ->missing('event.data')
                ->etc());
    }
}
