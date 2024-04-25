<?php

namespace Artwork\Modules\Budget\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class BudgetVerified extends Notification
{
    use Queueable;

    protected ?stdClass $notificationData = null;

    protected array $broadcastMessage = [];

    public function __construct($notificationData, array $broadcastMessage = [])
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

        $typeSettings = $user->notificationSettings()
            ->where('type', $this->notificationData->type)
            ->first();

        if ($typeSettings?->enabled_email && $typeSettings?->frequency === NotificationFrequencyEnum::IMMEDIATELY) {
            $channels[] = 'mail';
        }

        if ($typeSettings?->enabled_push && !empty($this->broadcastMessage)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(): MailMessage
    {
        $settings = app(GeneralSettings::class);
        return (new MailMessage())
            ->from(
                $settings->business_email !== '' ? $settings->business_email : 'noreply@artwork.software',
                'Artwork'
            )
            ->subject($this->notificationData->title)
            ->markdown('emails.simple-mail', ['notification' => $this->notificationData]);
    }

    public function toArray(): stdClass
    {
        return $this->notificationData;
    }
}
