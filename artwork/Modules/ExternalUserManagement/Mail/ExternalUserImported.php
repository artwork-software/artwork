<?php

namespace Artwork\Modules\ExternalUserManagement\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class ExternalUserImported extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {
    }

    public function build(): ExternalUserImported
    {
        /** @var GeneralSettings $settings */
        $settings = app(GeneralSettings::class);

        $fallbackSenderMail = Config::get('mail.system_mail');
        $senderAddress = $settings->business_email !== '' ? $settings->business_email : $fallbackSenderMail;
        $pageTitle = $settings->page_title !== '' ? $settings->page_title : Config::get('mail.fallback_page_title');

        $resetUrl = sprintf(
            '%s/reset-password/%s?email=%s',
            Config::get('app.url'),
            $this->token,
            urlencode($this->user->email)
        );

        return $this
            ->from($senderAddress, $pageTitle)
            ->subject('Willkommen bei ' . $pageTitle . ' – Passwort festlegen')
            ->markdown(
                'emails.external_user_imported',
                [
                    'name' => trim($this->user->first_name . ' ' . $this->user->last_name),
                    'page_title' => $pageTitle,
                    'url' => $resetUrl,
                    'sender_email' => $senderAddress,
                ]
            );
    }
}
