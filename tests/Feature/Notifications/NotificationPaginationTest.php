<?php

namespace Tests\Feature\Notifications;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class NotificationPaginationTest extends FeatureTestCase
{
    private function makeNotification(
        User $user,
        string $groupType = 'ROOMS',
        bool $read = false,
        array $buttons = ['show_project']
    ): string {
        $id = Str::uuid()->toString();

        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'App\\Notifications\\Generic',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'priority' => 1,
                'title' => 'x',
                'groupType' => $groupType,
                'buttons' => $buttons,
            ]),
            'read_at' => $read ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    #[Test]
    public function list_paginates_unread_notifications_of_a_group(): void
    {
        $user = $this->actingAsAdmin();

        for ($i = 0; $i < 7; $i++) {
            $this->makeNotification($user, 'ROOMS');
        }
        // excluded: archived ROOMS + unread of another group
        $this->makeNotification($user, 'ROOMS', read: true);
        $this->makeNotification($user, 'EVENTS');

        $this->getJson(route('notifications.list', ['groupType' => 'ROOMS', 'perPage' => 5]))
            ->assertOk()
            ->assertJsonPath('total', 7)
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(5, 'data');

        $this->getJson(route('notifications.list', ['groupType' => 'ROOMS', 'perPage' => 5, 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function list_returns_archived_notifications_when_requested(): void
    {
        $user = $this->actingAsAdmin();

        $this->makeNotification($user, 'ROOMS', read: true);
        $this->makeNotification($user, 'ROOMS', read: true);
        $this->makeNotification($user, 'ROOMS');

        $this->getJson(route('notifications.list', ['groupType' => 'ROOMS', 'status' => 'archived']))
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    #[Test]
    public function list_does_not_leak_other_users_notifications(): void
    {
        $user = $this->actingAsAdmin();
        $other = User::factory()->create();

        $this->makeNotification($user, 'ROOMS');
        $this->makeNotification($other, 'ROOMS');

        $this->getJson(route('notifications.list', ['groupType' => 'ROOMS']))
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    #[Test]
    public function list_requires_group_type(): void
    {
        $this->actingAsAdmin();

        $this->getJson(route('notifications.list'))->assertStatus(422);
    }

    #[Test]
    public function guests_cannot_access_list(): void
    {
        $this->getJson(route('notifications.list', ['groupType' => 'ROOMS']))->assertUnauthorized();
    }

    #[Test]
    public function archive_all_marks_archivable_unread_of_a_group_as_read(): void
    {
        $user = $this->actingAsAdmin();

        $archivable = $this->makeNotification($user, 'ROOMS', buttons: ['show_project']);
        $needsAction = $this->makeNotification($user, 'ROOMS', buttons: ['needs_action']);
        $otherGroup = $this->makeNotification($user, 'EVENTS', buttons: ['show_project']);

        $this->patchJson(route('notifications.setReadAtAll'), ['groupType' => 'ROOMS'])->assertOk();

        $this->assertNotNull(DB::table('notifications')->find($archivable)->read_at);
        $this->assertNull(DB::table('notifications')->find($needsAction)->read_at);
        $this->assertNull(DB::table('notifications')->find($otherGroup)->read_at);
    }
}
