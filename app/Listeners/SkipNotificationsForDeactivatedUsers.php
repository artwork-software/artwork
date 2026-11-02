<?php

namespace App\Listeners;

use Artwork\Modules\User\Models\User;
use Illuminate\Notifications\Events\NotificationSending;

/**
 * Deaktivierte Accounts bekommen keine Benachrichtigungen mehr – weder Mail noch Glocke noch Push.
 * false bricht den Versand über den jeweiligen Kanal ab (NotificationSender::shouldSendNotification).
 */
class SkipNotificationsForDeactivatedUsers
{
    public function handle(NotificationSending $event): ?bool
    {
        if ($event->notifiable instanceof User && $event->notifiable->isDeactivated()) {
            return false;
        }

        return null;
    }
}
