<?php

namespace Artwork\Modules\Notification\Jobs;

use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification as LaravelNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

/**
 * Sofort-Mail einer Benachrichtigung im Hintergrund (NotificationService::sendNotification). Vorher lief der
 * Versand im Request mit: ein langsamer oder nicht erreichbarer Mailserver hielt z. B. das Absenden einer
 * Raumanfrage bis zur Zeitüberschreitung auf, und die Person sah eine Fehlermeldung, obwohl alles gespeichert war.
 */
class SendNotificationMailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300];

    /**
     * Gelöschte Empfänger*innen: Mail entfällt still statt in failed_jobs zu landen.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly User $notifiable,
        public readonly LaravelNotification $notification,
    ) {
    }

    public function handle(): void
    {
        Notification::sendNow($this->notifiable, $this->notification, ['mail']);
    }
}
