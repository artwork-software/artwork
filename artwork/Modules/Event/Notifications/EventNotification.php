<?php

namespace Artwork\Modules\Event\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;

class EventNotification extends Notification implements ShouldBroadcast
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

        $typeSettings = $user->notificationSettings()
            ->where('type', $this->notificationData->type)
            ->first();

        if ($typeSettings?->enabled_email) {
            $channels[] = 'mail';
        }

        if ($typeSettings?->enabled_push && !empty($this->broadcastMessage)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function toMail(): MailMessage
    {
        $settings = app(GeneralSettings::class);
        $config = app(Repository::class);
        $systemMail = $config->get('mail.system_mail');
        $pageTitle = $settings->page_title !== '' ? $settings->page_title : $config->get('mail.fallback_page_title');
        return (new MailMessage())
            ->from(
                $settings->business_email !== '' ? $settings->business_email : $systemMail,
                $pageTitle
            )
            ->subject($this->notificationData->title)
            ->markdown('emails.simple-mail', ['notification' => $this->notificationData]);
    }

    public function toArray(): stdClass
    {
        return $this->notificationData;
    }
}
