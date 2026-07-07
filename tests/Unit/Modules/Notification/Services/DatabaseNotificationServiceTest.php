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

    #[Test]
    public function is_archivable_only_when_all_buttons_are_whitelisted(): void
    {
        $archivable = $this->createNotification(['data' => ['buttons' => ['show_project', 'accept']]]);
        $notArchivable = $this->createNotification(['data' => ['buttons' => ['show_project', 'some_action']]]);

        $this->assertTrue($this->service->isArchivable($archivable));
        $this->assertFalse($this->service->isArchivable($notArchivable));
    }

    #[Test]
    public function archive_all_unread_for_user_only_archives_archivable_in_given_group(): void
    {
        $user = User::factory()->create();

        $archivableRooms = $this->createForUser($user, ['groupType' => 'ROOMS', 'buttons' => ['show_project']]);
        $nonArchivableRooms = $this->createForUser($user, ['groupType' => 'ROOMS', 'buttons' => ['needs_action']]);
        $archivableEvents = $this->createForUser($user, ['groupType' => 'EVENTS', 'buttons' => ['accept']]);

        $count = $this->service->archiveAllUnreadForUser($user, 'ROOMS');

        $this->assertSame(1, $count);
        $this->assertNotNull($archivableRooms->fresh()->read_at);
        $this->assertNull($nonArchivableRooms->fresh()->read_at, 'non-archivable stays unread');
        $this->assertNull($archivableEvents->fresh()->read_at, 'other group untouched');
    }

    #[Test]
    public function archive_all_unread_for_user_without_group_archives_across_all_groups(): void
    {
        $user = User::factory()->create();

        $rooms = $this->createForUser($user, ['groupType' => 'ROOMS', 'buttons' => ['show_project']]);
        $events = $this->createForUser($user, ['groupType' => 'EVENTS', 'buttons' => ['accept']]);

        $count = $this->service->archiveAllUnreadForUser($user);

        $this->assertSame(2, $count);
        $this->assertNotNull($rooms->fresh()->read_at);
        $this->assertNotNull($events->fresh()->read_at);
    }

    #[Test]
    public function delete_all_for_user_removes_every_notification(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $a = $this->createForUser($user, ['groupType' => 'ROOMS', 'buttons' => []]);
        $b = $this->createForUser($user, ['groupType' => 'EVENTS', 'buttons' => []]);
        $foreign = $this->createForUser($other, ['groupType' => 'ROOMS', 'buttons' => []]);

        $deleted = $this->service->deleteAllForUser($user);

        $this->assertSame(2, $deleted);
        $this->assertDatabaseMissing('notifications', ['id' => $a->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $b->id]);
        // other user's notification must stay untouched
        $this->assertDatabaseHas('notifications', ['id' => $foreign->id]);
    }

    #[Test]
    public function delete_all_for_user_with_only_archived_keeps_unread(): void
    {
        $user = User::factory()->create();

        $unread = $this->createForUser($user, ['groupType' => 'ROOMS', 'buttons' => []]);
        $read = $this->createForUser($user, ['groupType' => 'ROOMS', 'buttons' => [], 'read_at' => Carbon::now()]);

        $deleted = $this->service->deleteAllForUser($user, onlyArchived: true);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $read->id]);
    }

    private function createForUser(User $user, array $data): DatabaseNotification
    {
        $readAt = $data['read_at'] ?? null;
        unset($data['read_at']);

        return DatabaseNotification::create([
            'id' => (string) Str::uuid(),
            'type' => 'TestNotification',
            'notifiable_type' => $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => $data,
            'read_at' => $readAt,
        ]);
    }
}
