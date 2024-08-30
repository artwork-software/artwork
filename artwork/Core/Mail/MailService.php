<?php

namespace Artwork\Core\Mail;

use Artwork\Modules\Invitation\Mail\InvitationCreated;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\User\Models\User;
use Illuminate\Config\Repository;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\MailManager;

class MailService
{
    public function __construct(
        private readonly Repository $config,
        private readonly MailManager $mailManager
    ) {
    }

    public function sendInvitationCreated(
        string $email,
        Invitation $invitation,
        User $admin_user,
        string $token
    ): void {
        $this->mailTo(
            $email,
            new InvitationCreated(
                $invitation,
                $admin_user,
                $token,
                $this->getFallbackPageTitle(),
                $this->getSystemMail()
            )
        );
    }

    final public function mailTo(string $email, Mailable $mailable): void
    {
        $this->mailManager->to($email)->send($mailable);
    }

    public function getSystemMail(): string
    {
        return $this->config->get('mail.system_mail');
    }

    public function getFallbackPageTitle(): string
    {
        return $this->config->get('mail.fallback_page_title');
    }
}
