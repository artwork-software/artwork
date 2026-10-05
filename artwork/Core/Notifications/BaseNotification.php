<?php

namespace Artwork\Core\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
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

    /**
     * Sofort-Mail (Frequenz „sofort“): eine Implementierung für alle Benachrichtigungstypen.
     * Der Titel ist bereits in der Sprache der Empfängerin übersetzt; die Festtexte des
     * Templates folgen derselben Sprache (users.language), sonst der App-Sprache.
     */
    public function toMail(mixed $notifiable): MailMessage
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
            ->markdown(
                'emails.simple-mail',
                [
                    'notification' => $this->notificationData,
                    'pageTitle' => $pageTitle,
                    'language' => self::languageOf($notifiable),
                ]
            );
    }

    public static function languageOf(mixed $notifiable): string
    {
        $language = is_object($notifiable) ? ($notifiable->language ?? null) : null;

        return is_string($language) && $language !== ''
            ? $language
            : (string) app(Repository::class)->get('app.locale');
    }

    public function toArray(): stdClass
    {
        return $this->notificationData;
    }
}
