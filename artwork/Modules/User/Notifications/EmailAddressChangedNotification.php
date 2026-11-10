<?php

namespace Artwork\Modules\User\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Hinweis an die bisherige Adresse, wenn sich die Login-E-Mail eines Kontos ändert. Mit einer
 * gestohlenen Sitzung ließe sich das Konto sonst per E-Mail-Wechsel und Passwort-Reset unbemerkt
 * dauerhaft übernehmen. Die neue Adresse steht bewusst nicht in der Mail.
 */
class EmailAddressChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $accountName)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $settings = app(GeneralSettings::class);
        $config = app(Repository::class);

        $pageTitle = $settings->page_title !== ''
            ? $settings->page_title
            : (string) $config->get('mail.fallback_page_title');
        $senderMail = $settings->business_email !== ''
            ? $settings->business_email
            : (string) $config->get('mail.system_mail');

        return (new MailMessage())
            ->from($senderMail, $pageTitle)
            ->subject(__('The email address of your :app account was changed', ['app' => $pageTitle]))
            ->greeting(__('Hello :name,', ['name' => $this->accountName]))
            ->line(__(
                'The login email address of your :app account was changed. Messages are now sent to the new address.',
                ['app' => $pageTitle]
            ))
            ->line(__('If you did not make this change, please contact your administration immediately.'));
    }
}
