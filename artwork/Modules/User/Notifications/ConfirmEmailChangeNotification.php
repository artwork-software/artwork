<?php

namespace Artwork\Modules\User\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Bestätigungslink an die neue Adresse, wenn jemand die eigene Login-E-Mail ändert. Erst der Klick
 * übernimmt die Adresse – sonst könnte sich jemand vorab die Adresse einer künftigen LDAP/OIDC-Person
 * sichern und so deren Verzeichnis-Identität samt Rollen an das eigene Konto binden.
 */
class ConfirmEmailChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $accountName,
        public readonly string $confirmationUrl,
        public readonly int $expiresInMinutes,
    ) {
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
            ->subject(__('Confirm the new email address of your :app account', ['app' => $pageTitle]))
            ->greeting(__('Hello :name,', ['name' => $this->accountName]))
            ->line(__(
                'Please confirm that this address should become the login email address of your :app account.',
                ['app' => $pageTitle]
            ))
            ->action(__('Confirm email address'), $this->confirmationUrl)
            ->line(__('The link is valid for :minutes minutes.', ['minutes' => $this->expiresInMinutes]))
            ->line(__('If you did not request this change, you can ignore this email.'));
    }
}
