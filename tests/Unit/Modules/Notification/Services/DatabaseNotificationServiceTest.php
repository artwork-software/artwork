<?php

namespace Tests\Unit\Modules\Notification\Services;

use Artwork\Modules\Notification\Services\DatabaseNotificationService;
use Artwork\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class DatabaseNotificationServiceTest extends TestCase
{
    private DatabaseNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseNotificationService::class);
    }

    private function createNotification(array $overrides = []): DatabaseNotification
    {
        $user = User::factory()->create();

        return DatabaseNotification::create(array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['key' => 'value'],
            'read_at' => null,
        ], $overrides));
    }

    #[Test]
    public function find_returns_notification_when_exists(): void
    {
        $notification = $this->createNotification();

        $result = $this->service->find($notification->id);

        $this->assertNotNull($result);
        $this->assertSame($notification->id, $result->id);
    }

    #[Test]
    public function find_returns_null_when_missing(): void
    {
        $this->assertNull($this->service->find('00000000-0000-0000-0000-000000000000'));
    }

    #[Test]
    public function update_sent_in_summary_persists_flag(): void
    {
        $notification = $this->createNotification();

        $result = $this->service->updateSentInSummary($notification, true);

        $this->assertNotNull($result);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'sent_in_summary' => true,
        ]);
    }

    #[Test]
    public function delete_by_key_deletes_matching_notification(): void
    {
        $notification = $this->createNotification([
            'data' => ['notificationKey' => 'unique-key-abc'],
        ]);

        $result = $this->service->deleteByKey('unique-key-abc');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    #[Test]
    public function remove_archived_notifications_older_than_thirty_days_purges_old_ones(): void
    {
        $oldRead = $this->createNotification([
            'read_at' => Carbon::now()->subDays(35),
        ]);
        $recentRead = $this->createNotification([
            'read_at' => Carbon::now()->subDays(5),
        ]);

        $this->service->removeArchivedNotificationsOlderThanThirtyDays();

        $this->assertDatabaseMissing('notifications', ['id' => $oldRead->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentRead->id]);
    }

    #[Test]
    public function remove_unread_notifications_older_than_one_year_purges_old_unread(): void
    {
        $oldUnread = $this->createNotification();
        // Override created_at to be older than 1 year
        $oldUnread->forceFill(['created_at' => Carbon::now()->subYears(2)])->save();

        $recentUnread = $this->createNotification();

        $this->service->removeUnreadNotificationsOlderThanOneYear();

        $this->assertDatabaseMissing('notifications', ['id' => $oldUnread->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentUnread->id]);
    }
}
