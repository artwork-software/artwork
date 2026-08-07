<?php

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // NotificationSettings werden nur bei User-Anlage aus dem Enum erzeugt —
    // ohne Backfill wäre der neue Typ für Bestandsuser unsichtbar/unkonfigurierbar.
    public function up(): void
    {
        $type = NotificationEnum::NOTIFICATION_SHIFT_WORKER_CONFIRMATION;

        $userIds = DB::table('users')->pluck('id');
        $existing = DB::table('notification_settings')
            ->where('type', $type->value)
            ->pluck('user_id')
            ->all();

        $now = now();
        $rows = [];
        foreach ($userIds as $userId) {
            if (in_array($userId, $existing, true)) {
                continue;
            }

            $rows[] = [
                'user_id' => $userId,
                'group_type' => $type->groupType(),
                'type' => $type->value,
                'title' => $type->title(),
                'description' => $type->description(),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('notification_settings')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('notification_settings')
            ->where('type', NotificationEnum::NOTIFICATION_SHIFT_WORKER_CONFIRMATION->value)
            ->delete();
    }
};
