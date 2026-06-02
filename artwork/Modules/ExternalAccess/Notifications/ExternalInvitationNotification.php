<?php

namespace Artwork\Modules\ExternalAccess\Notifications;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Project\Models\Project;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExternalInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $plainToken,
        public readonly ?User $invitedBy = null,
        public readonly ?Project $invitedFromProject = null,
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

        $fallbackSenderMail = (string) $config->get('mail.system_mail');
        $pageTitle = $settings->page_title !== ''
            ? $settings->page_title
            : (string) $config->get('mail.fallback_page_title');

        $company = !empty($settings->business_name) ? $settings->business_name : $pageTitle;

        $senderMail = $settings->business_email !== ''
            ? $settings->business_email
            : $fallbackSenderMail;

        $lifetimeMinutes = (int) config('external_access.login_token.lifetime_minutes', 15);
        $url = route('external.login.redeem', ['token' => $this->plainToken]);

        $invitedByName = $this->invitedBy !== null
            ? trim($this->invitedBy->first_name . ' ' . $this->invitedBy->last_name)
            : $company;

        return (new MailMessage())
            ->from($senderMail, $pageTitle)
            ->subject(__('You have been invited to :company', ['company' => $company]))
            ->markdown('emails.external_invitation', [
                'url' => $url,
                'company' => $company,
                'invitedByName' => $invitedByName,
                'projectName' => $this->invitedFromProject?->name,
                'lifetimeMinutes' => $lifetimeMinutes,
            ]);
    }
}
