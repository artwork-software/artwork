<?php

namespace App\Notifications;

use App\Enums\NotificationFrequency;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class GlobalUserNotification extends Notification
{
    use Queueable;

    protected ?stdClass $notificationData = null;

    public function __construct($notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * @return string[]
     */
    public function via($user): array
    {
        $channels = ['database'];

        $typeSettings = $user->notificationSettings()
            ->where('type', $this->notificationData->type)
            ->first();

        if ($typeSettings?->enabled_email && $typeSettings?->frequency === NotificationFrequency::IMMEDIATELY) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage())
            ->subject($this->notificationData->title)
            ->markdown('emails.simple-mail', ['notification' => $this->notificationData]);
    }

    public function toArray(): stdClass
    {
        return $this->notificationData;
    }
}
