<?php

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update group_type for NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED from BUDGET to DOCUMENTS
        DB::table('notification_settings')
            ->where('type', NotificationEnum::NOTIFICATION_CONTRACTS_DOCUMENT_CHANGED->value)
            ->update(['group_type' => 'DOCUMENTS']);

        // Add missing notification settings for all users
        $notificationTypes = NotificationEnum::cases();

        User::chunk(100, function ($users) use ($notificationTypes) {
            foreach ($users as $user) {
                $existingTypes = DB::table('notification_settings')
                    ->where('user_id', $user->id)
                    ->pluck('type')
                    ->toArray();

                foreach ($notificationTypes as $notificationType) {
                    if (!in_array($notificationType->value, $existingTypes)) {
                        DB::table('notification_settings')->insert([
                            'user_id' => $user->id,
                            'group_type' => $notificationType->groupType(),
                            'type' => $notificationType->value,
                            'title' => $notificationType->title(),
                            'description' => $notificationType->description(),
                            'enabled_email' => false,
                            'enabled_push' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        // No rollback needed - settings can remain
    }
};
