<?php

namespace Artwork\Core\Notifications;

use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class BaseNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected ?stdClass $notificationData = null;

    protected array $broadcastMessage = [];

    public function __construct($notificationData, $broadcastMessage = [])
    {
        $this->notificationData = $notificationData;
        $this->broadcastMessage = $broadcastMessage;
    }

    public function toBroadcast(): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->broadcastMessage
        ]);
    }

    /**
     * @return string[]
     */
    public function via($user): array
    {
        $channels = ['database'];

        /** @var NotificationSetting $typeSettings */
        $notificationSetting = $user->notificationSettings()
            ->where('type', $this->notificationData->type)
            ->first();

        if (is_null($notificationSetting)) {
            return $channels;
        }

        if (
            $notificationSetting->getAttribute('enabled_email') &&
            $notificationSetting->getAttribute('frequency') === NotificationFrequencyEnum::IMMEDIATELY
        ) {
            $channels[] = 'mail';
        }

        if (!empty($this->broadcastMessage) && $notificationSetting->getAttribute('enabled_push')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'mail' => 'sync',
        ];
    }

    public function toArray(): stdClass
    {
        return $this->notificationData;
    }
}
