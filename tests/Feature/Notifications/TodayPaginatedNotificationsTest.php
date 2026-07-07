<?php

namespace Tests\Feature\Notifications;

use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

final class TodayPaginatedNotificationsTest extends FeatureTestCase
{
    private function makeNotification(User $user, \Carbon\Carbon $createdAt, bool $read = false): void
    {
        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'App\\Notifications\\Generic',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(['priority' => 1, 'title' => 'x', 'groupType' => 'PROJECTS']),
            'read_at' => $read ? now() : null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    #[Test]
    public function it_paginates_only_todays_unread_notifications(): void
    {
        $user = $this->actingAsAdmin();

        // 7 unread today -> should be paginated
        for ($i = 0; $i < 7; $i++) {
            $this->makeNotification($user, now());
        }
        // excluded: read today, and unread yesterday
        $this->makeNotification($user, now(), read: true);
        $this->makeNotification($user, now()->subDay());

        $this->getJson(route('notifications.today', ['perPage' => 5]))
            ->assertOk()
            ->assertJsonPath('total', 7)
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(5, 'data');

        $this->getJson(route('notifications.today', ['perPage' => 5, 'page' => 2]))
            ->assertOk()
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_does_not_leak_other_users_notifications(): void
    {
        $user = $this->actingAsAdmin();
        $other = User::factory()->create();

        $this->makeNotification($user, now());
        $this->makeNotification($other, now());
        $this->makeNotification($other, now());

        $this->getJson(route('notifications.today'))
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    #[Test]
    public function guests_cannot_access_it(): void
    {
        $this->getJson(route('notifications.today'))->assertUnauthorized();
    }
}
