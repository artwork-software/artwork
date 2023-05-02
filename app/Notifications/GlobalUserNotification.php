<?php

namespace App\Notifications;

use App\Enums\NotificationFrequency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use stdClass;

class GlobalUserNotification extends Notification
{
    use Queueable;

    protected ?stdClass $notificationData = null;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($user)
    {
        $channels = ['database'];

        $typeSettings = $user->notificationSettings()
            ->where('type', $this->notificationData->type)
            ->first();

        if($typeSettings?->enabled_email && $typeSettings?->frequency === NotificationFrequency::IMMEDIATELY) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->notificationData->title)
            ->markdown('emails.simple-mail', ['notification' => $this->notificationData]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return stdClass
     */
    public function toArray(mixed $notifiable): stdClass
    {
        return $this->notificationData;
    }
}
