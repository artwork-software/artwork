<?php

namespace App\Notifications;

use App\Enums\NotificationFrequency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectNotification extends Notification implements ShouldBroadcast
{
    use Queueable;
    protected array $notificationData = [];
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($notificationData)
    {
        $this->notificationData = $notificationData;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => "Test"
        ]);
    }

    public function via($user)
    {
        $channels = ['database'];

        $typeSettings = $user->notificationSettings()
            ->where('type', $this->notificationData['type'])
            ->first();

        if($typeSettings?->enabled_email && $typeSettings?->frequency === NotificationFrequency::IMMEDIATELY) {
            $channels[] = 'mail';
        }

        if($typeSettings?->enabled_push) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notifications Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return $this->notificationData;
    }
}
