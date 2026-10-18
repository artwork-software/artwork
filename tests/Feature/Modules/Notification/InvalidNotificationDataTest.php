<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Eine einzige Meldung mit ungültigem JSON in notifications.data darf weder die Release-Migrationen
 * noch artwork:update abbrechen: MariaDB wertet JSON_EXTRACT im Strict-Mode bei schreibenden
 * Anweisungen als Fehler (4038), nicht als Warnung.
 */
final class InvalidNotificationDataTest extends FeatureTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function insertNotification(string $data): string
    {
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => 'Artwork\\Core\\Notifications\\BaseNotification',
            'notifiable_type' => $this->user->getMorphClass(),
            'notifiable_id' => $this->user->id,
            'data' => $data,
            'sent_in_summary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function runMigration(string $file): void
    {
        (require database_path('migrations/' . $file))->up();
    }

    #[Test]
    public function generated_group_column_tolerates_invalid_json(): void
    {
        $broken = $this->insertNotification('{kaputt');
        $valid = $this->insertNotification(json_encode(['type' => 'ROOM_REQUEST', 'groupType' => 'ROOMS']));

        $this->assertNull(DB::table('notifications')->where('id', $broken)->value('groupType'));
        $this->assertSame('ROOMS', DB::table('notifications')->where('id', $valid)->value('groupType'));
    }

    #[Test]
    public function repair_migration_deletes_only_notifications_with_invalid_json(): void
    {
        $broken = $this->insertNotification('{kaputt');
        $valid = $this->insertNotification(json_encode(['type' => 'ROOM_REQUEST']));

        $this->runMigration('2026_10_05_110150_delete_notifications_with_invalid_json.php');

        $this->assertFalse(DB::table('notifications')->where('id', $broken)->exists());
        $this->assertTrue(DB::table('notifications')->where('id', $valid)->exists());
    }

    #[Test]
    public function release_data_migrations_skip_invalid_json(): void
    {
        $this->insertNotification('{kaputt');
        $roomRequest = $this->insertNotification(json_encode([
            'type' => 'ROOM_REQUEST',
            'groupType' => 'EVENTS',
            'created_by' => [
                'id' => 1,
                'first_name' => 'A',
                'last_name' => 'B',
                'profile_photo_url' => 'x',
                'email' => 'secret@example.com',
            ],
        ]));

        $this->runMigration('2026_10_05_225021_slim_created_by_in_notifications_data.php');
        $this->runMigration('2026_10_06_000100_move_room_request_notifications_to_matching_groups.php');

        $data = json_decode(DB::table('notifications')->where('id', $roomRequest)->value('data'), true);
        $this->assertSame('ROOMS', $data['groupType']);
        $this->assertArrayNotHasKey('email', $data['created_by']);
    }

    #[Test]
    public function update_defaults_and_backlog_marking_skip_invalid_json(): void
    {
        $broken = $this->insertNotification('{kaputt');
        $backlog = $this->insertNotification(json_encode(['type' => NotificationEnum::NOTIFICATION_TEAM->value]));

        $this->assertGreaterThan(0, app(NotificationSettingService::class)->ensureDefaultsForAllUsers());

        $this->assertTrue((bool) DB::table('notifications')->where('id', $backlog)->value('sent_in_summary'));
        $this->assertFalse((bool) DB::table('notifications')->where('id', $broken)->value('sent_in_summary'));
    }
}
