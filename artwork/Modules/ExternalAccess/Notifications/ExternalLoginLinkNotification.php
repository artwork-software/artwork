<?php

namespace Artwork\Modules\ExternalAccess\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExternalLoginLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $plainToken)
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

        $fallbackSenderMail = (string) $config->get('mail.system_mail');
        $pageTitle = $settings->page_title !== ''
            ? $settings->page_title
            : (string) $config->get('mail.fallback_page_title');

        $senderMail = $settings->business_email !== ''
            ? $settings->business_email
            : $fallbackSenderMail;

        $lifetimeMinutes = (int) config('external_access.login_token.lifetime_minutes', 15);
        $url = route('external.login.redeem', ['token' => $this->plainToken]);

        return (new MailMessage())
            ->from($senderMail, $pageTitle)
            ->subject(__('Your login link for :app', ['app' => $pageTitle]))
            ->markdown('emails.external_login_link', [
                'url' => $url,
                'page_title' => $pageTitle,
                'lifetime_minutes' => $lifetimeMinutes,
            ]);
    }
}
