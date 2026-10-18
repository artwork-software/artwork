<?php

namespace Tests\Feature\Modules\Notification;

use Artwork\Modules\Notification\Services\NotificationSettingService;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

/**
 * Die Gruppen-Schalter filtern auf notification_settings.group_type: nach dem Gruppentausch der
 * Raumanfrage-Typen muss die Migration die Einstellungszeilen mitziehen (nicht erst artwork:update).
 */
final class RoomRequestSettingsGroupMigrationTest extends FeatureTestCase
{
    #[Test]
    public function room_request_settings_move_to_their_new_groups(): void
    {
        $user = User::factory()->create();
        app(NotificationSettingService::class)->ensureDefaultsForUser($user);
        // Stand vor dem Tausch
        DB::table('notification_settings')->where('user_id', $user->id)->where('type', 'ROOM_REQUEST')
            ->update(['group_type' => 'EVENTS']);
        DB::table('notification_settings')->where('user_id', $user->id)->where('type', 'NOTIFICATION_UPSERT_ROOM_REQUEST')
            ->update(['group_type' => 'ROOMS']);

        $migration = require database_path(
            'migrations/2026_10_06_020000_move_room_request_notification_settings_to_matching_groups.php'
        );
        $migration->up();

        $groups = DB::table('notification_settings')->where('user_id', $user->id)
            ->whereIn('type', ['ROOM_REQUEST', 'NOTIFICATION_UPSERT_ROOM_REQUEST'])
            ->pluck('group_type', 'type')
            ->all();
        ksort($groups);
        $this->assertSame(['NOTIFICATION_UPSERT_ROOM_REQUEST' => 'EVENTS', 'ROOM_REQUEST' => 'ROOMS'], $groups);
    }
}
