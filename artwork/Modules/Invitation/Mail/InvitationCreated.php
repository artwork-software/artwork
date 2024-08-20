<?php

namespace Artwork\Modules\Invitation\Mail;

use Artwork\Modules\GeneralSettings\Models\GeneralSettings;
use Artwork\Modules\Invitation\Models\Invitation;
use Artwork\Modules\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationCreated extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Invitation $invitation, public Authenticatable $user, public string $token)
    {
    }

    public function build(): InvitationCreated
    {
        /** @var GeneralSettings $settings */
        $settings = app(GeneralSettings::class);
        $pageTitle = $settings->page_title !== '' ? $settings->page_title : 'Artwork';
        $email = $settings->invitation_email !== '' ? $settings->invitation_email : User::query()->find(1)?->email;

        return $this
            ->from(
                $settings->invitation_email !== '' ? $settings->invitation_email : 'noreply@artwork.software',
                'Artwork'
            )
            ->replyTo($this->user->email)
            ->subject("Einladung")
            ->markdown(
                'emails.invitations',
                [
                    'token' => $this->token,
                    'page_title' => $pageTitle,
                    'email' => $email
                ]
            );
    }
}
